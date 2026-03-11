<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateUpgradeCommand extends Command
{
    protected $signature = 'app:generate-upgrade
        {--tag= : 指定基准 git tag（默认：最新 tag）}
        {--ver= : 手动指定目标版本号（默认：自动递增 patch）}
        {--dry-run : 仅预览生成内容，不写入文件}';

    protected $description = '根据 git diff 自动生成多租户升级脚本';

    public function handle(): int
    {
        $baseTag = $this->getBaseTag();
        if (! $baseTag) {
            $this->error('未找到 git tag，请先创建一个版本 tag（如 git tag v1.0.0）');
            return self::FAILURE;
        }

        $this->info("基准 tag: {$baseTag}");

        $nextVersion = $this->option('ver')
            ? ltrim($this->option('ver'), 'v')
            : $this->calculateNextVersion($baseTag);
        $className = 'Version' . str_replace('.', '', $nextVersion);
        $filePath = app_path("Upgrades/Versions/{$className}.php");

        if (file_exists($filePath)) {
            $this->error("版本类 {$className} 已存在: {$filePath}");
            return self::FAILURE;
        }

        $this->info("目标版本: {$nextVersion} (类名: {$className})");

        $changes = $this->getChangedMigrations($baseTag);

        // 显示变动摘要
        $this->displayChangeSummary($changes);

        // 解析所有变动
        $schemaOperations = $this->parseAllChanges($baseTag, $changes);

        // 生成代码
        $code = $this->buildUpgradeCode($className, $nextVersion, $schemaOperations);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('=== 生成的 PHP 代码 ===');
            $this->line($code);
            $this->newLine();
            $this->info('（dry-run 模式，未写入文件）');
            return self::SUCCESS;
        }

        file_put_contents($filePath, $code);
        $this->info("升级脚本已生成: {$filePath}");

        return self::SUCCESS;
    }

    /**
     * 获取基准 tag
     */
    private function getBaseTag(): ?string
    {
        if ($tag = $this->option('tag')) {
            return $tag;
        }

        $tag = trim($this->runGit('describe --tags --abbrev=0') ?? '');

        return $tag ?: null;
    }

    /**
     * 递增版本号
     */
    protected function calculateNextVersion(string $tag): string
    {
        $version = ltrim($tag, 'v');
        $parts = explode('.', $version);

        // 确保至少有三段版本号
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        // 递增 patch 版本
        $parts[2] = (int) $parts[2] + 1;

        return implode('.', $parts);
    }

    /**
     * 获取变动的 migration 文件，按新增/修改/删除分类
     */
    private function getChangedMigrations(string $tag): array
    {
        $basePath = 'database/migrations/';

        $added = $this->gitDiffFiles($tag, 'A', $basePath);
        $modified = $this->gitDiffFiles($tag, 'M', $basePath);
        $deleted = $this->gitDiffFiles($tag, 'D', $basePath);

        return [
            'added'    => $added,
            'modified' => $modified,
            'deleted'  => $deleted,
        ];
    }

    /**
     * 在项目根目录执行 git 命令
     */
    private function runGit(string $args): string
    {
        $cwd = getcwd();
        chdir(base_path());
        $redirect = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';
        $output = trim(shell_exec("git {$args} {$redirect}") ?? '');
        chdir($cwd);

        return $output;
    }

    /**
     * 执行 git diff 获取指定类型的文件列表
     */
    private function gitDiffFiles(string $tag, string $filter, string $path): array
    {
        $output = $this->runGit(sprintf(
            'diff %s..HEAD --diff-filter=%s --name-only -- %s',
            escapeshellarg($tag),
            escapeshellarg($filter),
            escapeshellarg($path)
        ));

        return $output ? array_filter(explode("\n", $output)) : [];
    }

    /**
     * 显示变动摘要
     */
    private function displayChangeSummary(array $changes): void
    {
        if (empty($changes['added']) && empty($changes['modified']) && empty($changes['deleted'])) {
            $this->info('未检测到 migration 变动，将生成仅含 updateHisVersion() 的最小版本类');
            return;
        }

        $rows = [];
        foreach ($changes['added'] as $file) {
            $rows[] = ['新增', $this->getMigrationType($file), basename($file)];
        }
        foreach ($changes['modified'] as $file) {
            $rows[] = ['修改', $this->getMigrationType($file), basename($file)];
        }
        foreach ($changes['deleted'] as $file) {
            $rows[] = ['删除', $this->getMigrationType($file), basename($file)];
        }

        $this->table(['类型', '作用域', '文件'], $rows);

        if (! empty($changes['deleted'])) {
            $this->warn('⚠ 检测到已删除的 migration 文件，已自动生成 dropIfExists，请确认是否需要删除这些表');
        }
    }

    /**
     * 判断 migration 类型（tenant/admin）
     */
    protected function getMigrationType(string $path): string
    {
        if (str_contains($path, '/tenant/')) {
            return 'tenant';
        }
        if (str_contains($path, '/admin/')) {
            return 'admin';
        }

        return 'root';
    }

    /**
     * 解析所有变动
     */
    private function parseAllChanges(string $tag, array $changes): array
    {
        $operations = [
            'tenant' => [],
            'admin'  => [],
        ];

        // 解析新增 migration
        foreach ($changes['added'] as $file) {
            $schemas = $this->parseNewMigration($file);
            $type = $this->getMigrationType($file);
            $key = $type === 'admin' ? 'admin' : 'tenant';
            foreach ($schemas as $schema) {
                $operations[$key][] = $schema;
            }
        }

        // 解析修改 migration
        foreach ($changes['modified'] as $file) {
            $schemas = $this->parseModifiedMigration($tag, $file);
            $type = $this->getMigrationType($file);
            $key = $type === 'admin' ? 'admin' : 'tenant';
            foreach ($schemas as $schema) {
                $operations[$key][] = $schema;
            }
        }

        // 解析删除 migration
        foreach ($changes['deleted'] as $file) {
            $schemas = $this->parseDeletedMigration($tag, $file);
            $type = $this->getMigrationType($file);
            $key = $type === 'admin' ? 'admin' : 'tenant';
            foreach ($schemas as $schema) {
                $operations[$key][] = $schema;
            }
        }

        return $operations;
    }

    /**
     * 解析新增 migration 的 Schema::create 代码块
     */
    private function parseNewMigration(string $file): array
    {
        $fullPath = base_path($file);
        if (! file_exists($fullPath)) {
            return [];
        }

        $content = file_get_contents($fullPath);
        $schemas = [];

        // 提取所有 Schema::create(...) 块
        $offset = 0;
        while (($pos = strpos($content, 'Schema::create(', $offset)) !== false) {
            $block = $this->extractBalancedBlock($content, $pos);
            if ($block) {
                $schemas[] = [
                    'type'  => 'create',
                    'code'  => $block,
                    'table' => $this->extractTableNameFromCode($block),
                ];
            }
            $offset = $pos + 1;
        }

        return $schemas;
    }

    /**
     * 解析删除 migration，从 git 历史中读取旧文件内容提取表名，生成 dropIfExists
     */
    protected function parseDeletedMigration(string $tag, string $file): array
    {
        // 从 git 历史中读取已删除文件的内容
        $content = $this->runGit(sprintf('show %s:%s', escapeshellarg($tag), escapeshellarg($file)));

        if (empty($content)) {
            return [];
        }

        $tableNames = $this->extractTableNamesFromContent($content);
        $schemas = [];

        foreach ($tableNames as $tableName) {
            $schemas[] = [
                'type'  => 'drop',
                'code'  => "// TODO: Review - 确认是否需要删除表 {$tableName}（来自已删除的 migration: " . basename($file) . "）\n        Schema::dropIfExists('{$tableName}')",
                'table' => $tableName,
            ];
        }

        return $schemas;
    }

    /**
     * 从 migration 文件内容中提取所有 Schema::create 的表名
     */
    protected function extractTableNamesFromContent(string $content): array
    {
        preg_match_all("/Schema::create\s*\(\s*['\"](\w+)['\"]/", $content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * 使用花括号匹配算法提取完整的代码块
     */
    protected function extractBalancedBlock(string $content, int $start): ?string
    {
        // 找到第一个 { 的位置
        $braceStart = strpos($content, '{', $start);
        if ($braceStart === false) {
            return null;
        }

        $depth = 0;
        $len = strlen($content);

        for ($i = $braceStart; $i < $len; $i++) {
            if ($content[$i] === '{') {
                $depth++;
            } elseif ($content[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    // 再往后找到闭合的 ) 和可能的 ;
                    $end = $i + 1;
                    // 跳过空白字符
                    while ($end < $len && in_array($content[$end], [' ', "\t", "\n", "\r"])) {
                        $end++;
                    }
                    // 如果后面有 )，包含它
                    if ($end < $len && $content[$end] === ')') {
                        $end++;
                    }

                    return substr($content, $start, $end - $start);
                }
            }
        }

        return null;
    }

    /**
     * 从代码中提取表名
     */
    protected function extractTableNameFromCode(string $code): ?string
    {
        if (preg_match("/Schema::(?:create|table)\s*\(\s*['\"](\w+)['\"]/", $code, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * 解析修改 migration 的列差异
     */
    private function parseModifiedMigration(string $tag, string $file): array
    {
        $fullPath = base_path($file);
        if (! file_exists($fullPath)) {
            return [];
        }

        // 获取 unified diff
        $diff = $this->runGit(sprintf('diff %s..HEAD -- %s', escapeshellarg($tag), escapeshellarg($file)));

        if (empty(trim($diff))) {
            return [];
        }

        // 读取完整文件内容用于提取表名和列顺序
        $fileContent = file_get_contents($fullPath);

        // 解析 diff hunks
        $hunks = $this->parseDiffHunks($diff);
        $results = [];

        foreach ($hunks as $hunk) {
            $tableName = $this->extractTableContext($fileContent, $hunk['new_start'], $hunk['new_count'] ?? 1);
            if (! $tableName) {
                continue;
            }

            // 提取目标表的完整列顺序
            $columnOrder = $this->extractColumnOrder($fileContent, $tableName);

            $columnChanges = $this->classifyColumnChanges($hunk['added_lines'], $hunk['removed_lines']);

            if (empty($columnChanges)) {
                continue;
            }

            // 为每个变更计算 after 位置
            $columnChanges = $this->applyAfterPositions($columnChanges, $columnOrder);

            $code = $this->buildTableModification($tableName, $columnChanges);
            if ($code) {
                $results[] = [
                    'type'  => 'modify',
                    'code'  => $code,
                    'table' => $tableName,
                ];
            }
        }

        // 合并同表操作
        return $this->mergeTableOperations($results);
    }

    /**
     * 从 migration 文件的 Schema::create 块中提取完整的列顺序
     */
    protected function extractColumnOrder(string $fileContent, string $tableName): array
    {
        // 找到 Schema::create('tableName', ...) 块
        $pattern = "/Schema::create\s*\(\s*['\"]" . preg_quote($tableName, '/') . "['\"]/";
        if (! preg_match($pattern, $fileContent, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $block = $this->extractBalancedBlock($fileContent, $m[0][1]);
        if (! $block) {
            return [];
        }

        $columns = [];
        $blockLines = explode("\n", $block);

        foreach ($blockLines as $line) {
            $trimmed = trim($line);

            // 匹配 $table->type('column_name') 形式的列定义
            if (preg_match('/\$table->(\w+)\s*\(\s*[\'"](\w+)[\'"]/', $trimmed, $colMatch)) {
                $method = $colMatch[1];
                $colName = $colMatch[2];

                // 跳过非列定义方法
                if (in_array($method, ['comment', 'index', 'unique', 'primary', 'foreign', 'dropColumn', 'dropIndex', 'dropUnique', 'dropPrimary', 'dropForeign'])) {
                    continue;
                }

                $columns[] = $colName;
                continue;
            }

            // 处理 $table->id() -> 'id' (或自定义名称)
            if (preg_match('/\$table->id\s*\(\s*(?:[\'"](\w+)[\'"])?\s*\)/', $trimmed, $idMatch)) {
                $columns[] = $idMatch[1] ?? 'id';
                continue;
            }

            // 处理 $table->uuid() -> 'uuid' (或自定义名称)
            if (preg_match('/\$table->uuid\s*\(\s*(?:[\'"](\w+)[\'"])?\s*\)/', $trimmed, $uuidMatch)) {
                $columns[] = $uuidMatch[1] ?? 'uuid';
                continue;
            }

            // 处理 timestamps() -> created_at, updated_at
            if (preg_match('/\$table->timestamps\s*\(/', $trimmed)) {
                $columns[] = 'created_at';
                $columns[] = 'updated_at';
                continue;
            }

            // 处理 nullableTimestamps()
            if (preg_match('/\$table->nullableTimestamps\s*\(/', $trimmed)) {
                $columns[] = 'created_at';
                $columns[] = 'updated_at';
                continue;
            }

            // 处理 softDeletes() -> deleted_at
            if (preg_match('/\$table->softDeletes\s*\(/', $trimmed)) {
                $columns[] = 'deleted_at';
                continue;
            }

            // 处理 rememberToken() -> remember_token
            if (preg_match('/\$table->rememberToken\s*\(/', $trimmed)) {
                $columns[] = 'remember_token';
                continue;
            }
        }

        return $columns;
    }

    /**
     * 为变更列计算 ->after() 位置
     */
    protected function applyAfterPositions(array $columnChanges, array $columnOrder): array
    {
        if (empty($columnOrder)) {
            return $columnChanges;
        }

        foreach ($columnChanges as &$change) {
            if (! in_array($change['action'], ['add', 'replace'])) {
                continue;
            }

            $colName = $change['column'];
            $pos = array_search($colName, $columnOrder);

            if ($pos === false) {
                continue;
            }

            if ($pos === 0) {
                $change['after'] = '__first__';
            } else {
                $change['after'] = $columnOrder[$pos - 1];
            }
        }
        unset($change);

        return $columnChanges;
    }

    /**
     * 解析 diff hunks
     */
    protected function parseDiffHunks(string $diff): array
    {
        $hunks = [];
        $lines = explode("\n", $diff);
        $currentHunk = null;

        foreach ($lines as $line) {
            // 匹配 hunk header: @@ -old_start,old_count +new_start,new_count @@
            if (preg_match('/^@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@/', $line, $m)) {
                if ($currentHunk) {
                    $hunks[] = $currentHunk;
                }
                $currentHunk = [
                    'old_start'     => (int) $m[1],
                    'old_count'     => ! empty($m[2]) ? (int) $m[2] : 1,
                    'new_start'     => (int) $m[3],
                    'new_count'     => ! empty($m[4]) ? (int) $m[4] : 1,
                    'added_lines'   => [],
                    'removed_lines' => [],
                ];
                continue;
            }

            if (! $currentHunk) {
                continue;
            }

            if (str_starts_with($line, '+') && ! str_starts_with($line, '+++')) {
                $currentHunk['added_lines'][] = substr($line, 1);
            } elseif (str_starts_with($line, '-') && ! str_starts_with($line, '---')) {
                $currentHunk['removed_lines'][] = substr($line, 1);
            }
        }

        if ($currentHunk) {
            $hunks[] = $currentHunk;
        }

        return $hunks;
    }

    /**
     * 从 hunk 行号定位所在的表名
     */
    protected function extractTableContext(string $fileContent, int $lineNumber, int $lineCount = 0): ?string
    {
        $lines = explode("\n", $fileContent);
        $totalLines = count($lines);

        // 从 hunk 行号往上查找最近的 Schema::create 或 Schema::table
        for ($i = min($lineNumber - 1, $totalLines - 1); $i >= 0; $i--) {
            if (preg_match("/Schema::(?:create|table)\s*\(\s*['\"](\w+)['\"]/", $lines[$i], $m)) {
                return $m[1];
            }
        }

        // 往上没找到时，在 hunk 覆盖范围内向下查找
        $end = min($lineNumber - 1 + $lineCount, $totalLines - 1);
        for ($i = $lineNumber; $i <= $end; $i++) {
            if (preg_match("/Schema::(?:create|table)\s*\(\s*['\"](\w+)['\"]/", $lines[$i], $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * 将 +/- 行分类为增/删/改列
     */
    protected function classifyColumnChanges(array $addedLines, array $removedLines): array
    {
        $added = $this->extractColumnDefinitions($addedLines);
        $removed = $this->extractColumnDefinitions($removedLines);

        $changes = [];

        // 新增列：在 added 中有但 removed 中没有
        foreach ($added as $colName => $definition) {
            if (! isset($removed[$colName])) {
                $changes[] = [
                    'action'     => 'add',
                    'column'     => $colName,
                    'definition' => $definition,
                ];
            }
        }

        // 删除列：在 removed 中有但 added 中没有
        foreach ($removed as $colName => $definition) {
            if (! isset($added[$colName])) {
                $changes[] = [
                    'action'     => 'drop',
                    'column'     => $colName,
                    'definition' => $definition,
                ];
            }
        }

        // 替换列：同名但定义不同
        foreach ($added as $colName => $definition) {
            if (isset($removed[$colName])) {
                $addedDef = $this->normalizeDefinition($definition);
                $removedDef = $this->normalizeDefinition($removed[$colName]);

                // comment 变更也需要生成升级代码
                if ($this->isOnlyCommentChange($addedDef, $removedDef)) {
                    $changes[] = [
                        'action'         => 'comment',
                        'column'         => $colName,
                        'definition'     => $definition,
                        'old_definition' => $removed[$colName],
                    ];
                    continue;
                }

                $changes[] = [
                    'action'         => 'replace',
                    'column'         => $colName,
                    'definition'     => $definition,
                    'old_definition' => $removed[$colName],
                ];
            }
        }

        return $changes;
    }

    /**
     * 从 diff 行中提取列定义
     */
    protected function extractColumnDefinitions(array $lines): array
    {
        $columns = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/\$table->(\w+)\s*\(\s*[\'"](\w+)[\'"]/', $trimmed, $m)) {
                $method = $m[1];
                $colName = $m[2];

                // 跳过非列定义方法
                if (in_array($method, ['comment', 'index', 'unique', 'primary', 'foreign', 'dropColumn', 'dropIndex', 'dropUnique', 'dropPrimary', 'dropForeign'])) {
                    continue;
                }

                $columns[$colName] = $trimmed;
            }
        }

        return $columns;
    }

    /**
     * 标准化定义用于比较（去除 comment）
     */
    protected function normalizeDefinition(string $definition): string
    {
        // 移除 ->comment(...) 部分
        return preg_replace('/->comment\s*\([^)]*\)/', '', $definition);
    }

    /**
     * 判断是否仅 comment 变更
     */
    protected function isOnlyCommentChange(string $a, string $b): bool
    {
        return trim($a, " \t;") === trim($b, " \t;");
    }

    /**
     * 构建表修改代码
     */
    protected function buildTableModification(string $tableName, array $columnChanges): ?string
    {
        if (empty($columnChanges)) {
            return null;
        }

        $lines = [];

        foreach ($columnChanges as $change) {
            switch ($change['action']) {
                case 'add':
                    $lines[] = '            ' . $this->appendAfter($change) . ';';
                    break;

                case 'drop':
                    $lines[] = "            \$table->dropColumn('{$change['column']}');";
                    break;

                case 'comment':
                    $lines[] = '            ' . $this->appendAfter($change) . '->change();';
                    break;

                case 'replace':
                    $lines[] = "            // TODO: Review - 列 {$change['column']} 定义变更，先删除旧列再添加新列";
                    $lines[] = "            \$table->dropColumn('{$change['column']}');";
                    break;
            }
        }

        // 替换列的新定义需要单独的 Schema::table 块
        $replaceLines = [];
        foreach ($columnChanges as $change) {
            if ($change['action'] === 'replace') {
                $replaceLines[] = '            ' . $this->appendAfter($change) . ';';
            }
        }

        $code = "Schema::table('{$tableName}', function (Blueprint \$table) {\n";
        $code .= implode("\n", $lines) . "\n";
        $code .= '        })';

        if (! empty($replaceLines)) {
            $code .= ";\n\n";
            $code .= "        // TODO: Review - 重新添加变更后的列\n";
            $code .= "        Schema::table('{$tableName}', function (Blueprint \$table) {\n";
            $code .= implode("\n", $replaceLines) . "\n";
            $code .= '        })';
        }

        return $code;
    }

    /**
     * 为列定义追加 ->after() 或 ->first()
     */
    protected function appendAfter(array $change): string
    {
        $def = rtrim($change['definition'], '; ');

        if (! empty($change['after'])) {
            if ($change['after'] === '__first__') {
                $def .= '->first()';
            } else {
                $def .= "->after('{$change['after']}')";
            }
        }

        return $def;
    }

    /**
     * 合并同表操作
     */
    protected function mergeTableOperations(array $operations): array
    {
        $merged = [];
        $seen = [];

        foreach ($operations as $op) {
            $key = $op['table'];
            if (isset($seen[$key])) {
                // 合并代码
                $merged[$seen[$key]]['code'] .= ";\n\n        " . $op['code'];
            } else {
                $seen[$key] = count($merged);
                $merged[] = $op;
            }
        }

        return array_values($merged);
    }

    /**
     * 渲染 Schema 操作代码，处理缩进
     */
    private function renderSchemaOperations(array $operations, int $baseIndent = 2): string
    {
        $indent = str_repeat('    ', $baseIndent);
        $lines = [];

        foreach ($operations as $op) {
            $table = $op['table'] ?? 'unknown';
            $type = match ($op['type']) {
                'create' => '创建表',
                'drop'   => '删除表',
                default  => '修改表',
            };

            $lines[] = "{$indent}info(\"\$tenantTag {$type} {$table}\");";
            $lines[] = "{$indent}" . $op['code'] . ';';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * 生成完整的 PHP 类文件
     */
    protected function buildUpgradeCode(string $className, string $version, array $schemaOperations): string
    {
        $hasTenantOps = ! empty($schemaOperations['tenant']);
        $hasAdminOps = ! empty($schemaOperations['admin']);
        $hasSchemaOps = $hasTenantOps || $hasAdminOps;

        // 构建 use 语句
        $uses = [];
        $uses[] = 'use App\Models\Admin\AdminParameter;';
        if ($hasSchemaOps) {
            $uses[] = 'use Illuminate\Database\Schema\Blueprint;';
            $uses[] = 'use Illuminate\Support\Facades\Schema;';
        }
        $uses[] = 'use Stancl\Tenancy\Facades\Tenancy;';
        sort($uses);

        // 构建 upgrade 方法体
        $upgradeBody = '';

        // 租户迁移操作
        if ($hasTenantOps) {
            $upgradeBody .= $this->renderSchemaOperations($schemaOperations['tenant']);
        }

        // Admin 迁移操作（包裹在 Tenancy::central 中）
        if ($hasAdminOps) {
            if ($hasTenantOps) {
                $upgradeBody .= "\n";
            }
            $upgradeBody .= "        // Admin 迁移（中央数据库）\n";
            $upgradeBody .= "        Tenancy::central(function () {\n";

            foreach ($schemaOperations['admin'] as $op) {
                $table = $op['table'] ?? 'unknown';
                $type = match ($op['type']) {
                    'create' => '创建表',
                    'drop'   => '删除表',
                    default  => '修改表',
                };
                $upgradeBody .= "            info(\"\$tenantTag {$type} {$table}\");\n";
                // 给 admin 操作增加额外缩进
                $code = $op['code'];
                $code = str_replace("\n", "\n    ", $code);
                $upgradeBody .= "            {$code};\n\n";
            }

            $upgradeBody = rtrim($upgradeBody) . "\n";
            $upgradeBody .= "        });\n\n";
        }

        // updateHisVersion 调用
        $upgradeBody .= "        // 更新系统版本号\n";
        $upgradeBody .= "        \$this->updateHisVersion();\n\n";
        $upgradeBody .= "        info(\"\$tenantTag {$version} 版本升级完成\");";

        $code = <<<PHP
<?php

namespace App\Upgrades\Versions;

{$this->formatUses($uses)}

class {$className} extends BaseVersion
{
    /**
     * 版本号
     */
    public function version(): string
    {
        return '{$version}';
    }

    /**
     * 升级方法
     */
    public function upgrade(): void
    {
        \$tenantTag = '[租户:' . tenant()->id . ' ' . tenant()->name . ']';
        info("\$tenantTag 开始执行 {$version} 版本升级");

{$upgradeBody}
    }

    /**
     * 更新系统版本号
     */
    private function updateHisVersion(): void
    {
        \$tenantTag = '[租户:' . tenant()->id . ' ' . tenant()->name . ']';
        Tenancy::central(function () {
            AdminParameter::query()
                ->where('name', 'his_version')
                ->update(['value' => '{$version}']);
        });

        info("\$tenantTag 系统版本号已更新到 {$version}");
    }
}

PHP;

        return $code;
    }

    /**
     * 格式化 use 语句
     */
    private function formatUses(array $uses): string
    {
        return implode("\n", $uses);
    }
}
