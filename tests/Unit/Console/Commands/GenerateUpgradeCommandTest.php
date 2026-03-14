<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GenerateUpgradeCommand;
use ReflectionMethod;
use Tests\TestCase;

class GenerateUpgradeCommandTest extends TestCase
{
    private GenerateUpgradeCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new GenerateUpgradeCommand;
    }

    /**
     * 调用 protected 方法的辅助
     */
    private function invoke(string $method, ...$args): mixed
    {
        $ref = new ReflectionMethod($this->command, $method);

        return $ref->invoke($this->command, ...$args);
    }

    // ========== calculateNextVersion ==========

    public function test_calculate_next_version_increments_patch(): void
    {
        $this->assertEquals('1.0.3', $this->invoke('calculateNextVersion', 'v1.0.2'));
    }

    public function test_calculate_next_version_strips_v_prefix(): void
    {
        $this->assertEquals('2.1.4', $this->invoke('calculateNextVersion', 'v2.1.3'));
    }

    public function test_calculate_next_version_without_v_prefix(): void
    {
        $this->assertEquals('1.0.1', $this->invoke('calculateNextVersion', '1.0.0'));
    }

    public function test_calculate_next_version_pads_missing_segments(): void
    {
        $this->assertEquals('1.0.1', $this->invoke('calculateNextVersion', 'v1'));
    }

    public function test_calculate_next_version_two_segments(): void
    {
        $this->assertEquals('1.2.1', $this->invoke('calculateNextVersion', 'v1.2'));
    }

    public function test_calculate_next_version_high_patch_number(): void
    {
        $this->assertEquals('1.0.100', $this->invoke('calculateNextVersion', 'v1.0.99'));
    }

    // ========== getMigrationType ==========

    public function test_get_migration_type_tenant(): void
    {
        $this->assertEquals('tenant', $this->invoke('getMigrationType', 'database/migrations/tenant/create_users.php'));
    }

    public function test_get_migration_type_admin(): void
    {
        $this->assertEquals('admin', $this->invoke('getMigrationType', 'database/migrations/admin/create_settings.php'));
    }

    public function test_get_migration_type_root(): void
    {
        $this->assertEquals('root', $this->invoke('getMigrationType', 'database/migrations/create_cache.php'));
    }

    // ========== extractTableNameFromCode ==========

    public function test_extract_table_name_from_create(): void
    {
        $code = "Schema::create('users', function (Blueprint \$table) {";
        $this->assertEquals('users', $this->invoke('extractTableNameFromCode', $code));
    }

    public function test_extract_table_name_from_table(): void
    {
        $code = "Schema::table('orders', function (Blueprint \$table) {";
        $this->assertEquals('orders', $this->invoke('extractTableNameFromCode', $code));
    }

    public function test_extract_table_name_double_quotes(): void
    {
        $code = 'Schema::create("products", function (Blueprint $table) {';
        $this->assertEquals('products', $this->invoke('extractTableNameFromCode', $code));
    }

    public function test_extract_table_name_returns_null_for_no_match(): void
    {
        $this->assertNull($this->invoke('extractTableNameFromCode', '$table->id();'));
    }

    // ========== extractBalancedBlock ==========

    public function test_extract_balanced_block_simple(): void
    {
        $content = "Schema::create('users', function (Blueprint \$table) {\n    \$table->id();\n})";
        $result = $this->invoke('extractBalancedBlock', $content, 0);
        $this->assertNotNull($result);
        $this->assertStringContainsString('Schema::create', $result);
        $this->assertStringContainsString('$table->id()', $result);
        $this->assertStringEndsWith(')', $result);
    }

    public function test_extract_balanced_block_nested_braces(): void
    {
        $content = "Schema::create('t', function (\$table) {\n    \$table->enum('s', ['a', 'b']);\n    if (true) { \$x = 1; }\n})";
        $result = $this->invoke('extractBalancedBlock', $content, 0);
        $this->assertNotNull($result);
        $this->assertStringEndsWith(')', $result);
    }

    public function test_extract_balanced_block_no_braces(): void
    {
        $this->assertNull($this->invoke('extractBalancedBlock', 'no braces here', 0));
    }

    public function test_extract_balanced_block_unclosed(): void
    {
        $this->assertNull($this->invoke('extractBalancedBlock', 'Schema::create("t", function() { open', 0));
    }

    public function test_extract_balanced_block_multiple_schemas(): void
    {
        $content = "Schema::create('a', function (\$t) {\n    \$t->id();\n});\n\nSchema::create('b', function (\$t) {\n    \$t->id();\n})";
        $result = $this->invoke('extractBalancedBlock', $content, 0);
        $this->assertStringContainsString("'a'", $result);
        $this->assertStringNotContainsString("'b'", $result);
    }

    // ========== parseDiffHunks ==========

    public function test_parse_diff_hunks_single_hunk(): void
    {
        $diff = "--- a/file.php\n+++ b/file.php\n@@ -10,5 +10,7 @@ some context\n context line\n+    \$table->string('name');\n-    \$table->text('name');\n another context";

        $hunks = $this->invoke('parseDiffHunks', $diff);
        $this->assertCount(1, $hunks);
        $this->assertEquals(10, $hunks[0]['old_start']);
        $this->assertEquals(10, $hunks[0]['new_start']);
        $this->assertCount(1, $hunks[0]['added_lines']);
        $this->assertCount(1, $hunks[0]['removed_lines']);
    }

    public function test_parse_diff_hunks_multiple_hunks(): void
    {
        $diff = "--- a/file.php\n+++ b/file.php\n@@ -5,3 +5,4 @@\n+    \$table->string('a');\n@@ -20,3 +21,4 @@\n+    \$table->string('b');";

        $hunks = $this->invoke('parseDiffHunks', $diff);
        $this->assertCount(2, $hunks);
        $this->assertEquals(5, $hunks[0]['new_start']);
        $this->assertEquals(21, $hunks[1]['new_start']);
    }

    public function test_parse_diff_hunks_ignores_file_headers(): void
    {
        $diff = "--- a/file.php\n+++ b/file.php\n@@ -1,3 +1,3 @@\n context\n+added\n-removed";
        $hunks = $this->invoke('parseDiffHunks', $diff);
        $this->assertCount(1, $hunks);
        $this->assertCount(1, $hunks[0]['added_lines']);
        $this->assertCount(1, $hunks[0]['removed_lines']);
    }

    public function test_parse_diff_hunks_empty_diff(): void
    {
        $this->assertEmpty($this->invoke('parseDiffHunks', ''));
    }

    public function test_parse_diff_hunks_hunk_without_count(): void
    {
        $diff = "@@ -1 +1 @@\n+new line\n-old line";
        $hunks = $this->invoke('parseDiffHunks', $diff);
        $this->assertCount(1, $hunks);
        $this->assertEquals(1, $hunks[0]['old_start']);
        $this->assertEquals(1, $hunks[0]['new_start']);
        $this->assertEquals(1, $hunks[0]['old_count']);
        $this->assertEquals(1, $hunks[0]['new_count']);
    }

    public function test_parse_diff_hunks_captures_counts(): void
    {
        $diff = "@@ -10,5 +10,7 @@\n+added";
        $hunks = $this->invoke('parseDiffHunks', $diff);
        $this->assertCount(1, $hunks);
        $this->assertEquals(5, $hunks[0]['old_count']);
        $this->assertEquals(7, $hunks[0]['new_count']);
    }

    // ========== extractTableContext ==========

    public function test_extract_table_context_finds_nearest_schema(): void
    {
        $content = "line 1\nSchema::create('users', function (Blueprint \$table) {\n    \$table->id();\n    \$table->string('name');\n});";

        $this->assertEquals('users', $this->invoke('extractTableContext', $content, 4));
    }

    public function test_extract_table_context_multiple_schemas(): void
    {
        $lines = [
            "Schema::create('orders', function (\$t) {",
            '    $t->id();',
            '});',
            '',
            "Schema::create('items', function (\$t) {",
            '    $t->id();',
            '    $t->string("name");',
            '});',
        ];
        $content = implode("\n", $lines);

        $this->assertEquals('items', $this->invoke('extractTableContext', $content, 7));
        $this->assertEquals('orders', $this->invoke('extractTableContext', $content, 2));
    }

    public function test_extract_table_context_no_schema_found(): void
    {
        $content = "line1\nline2\nline3";
        $this->assertNull($this->invoke('extractTableContext', $content, 2));
    }

    public function test_extract_table_context_line_beyond_file(): void
    {
        $content = "Schema::create('t', function () {\n})";
        // 行号超出文件行数，应安全回退
        $this->assertEquals('t', $this->invoke('extractTableContext', $content, 999));
    }

    public function test_extract_table_context_schema_below_hunk_start(): void
    {
        // 模拟 hunk 从文件开头开始（如 use 语句变更），Schema::create 在 hunk 范围内但在 hunk start 之下
        $lines = [
            '<?php',                                                // line 1
            '',                                                      // line 2
            'use Illuminate\Database\Schema\Blueprint;',            // line 3
            'use Illuminate\Support\Facades\Schema;',               // line 4
            '',                                                      // line 5
            'return new class extends Migration',                    // line 6
            '{',                                                     // line 7
            '    public function up(): void',                       // line 8
            '    {',                                                 // line 9
            "        Schema::create('customer_photos', function (Blueprint \$table) {", // line 10
            '            $table->id();',                           // line 11
            "            \$table->string('name');",                 // line 12
            '        });',                                           // line 13
            '    }',                                                 // line 14
            '};',                                                    // line 15
        ];
        $content = implode("\n", $lines);

        // hunk 从第 1 行开始，覆盖 15 行，Schema::create 在第 10 行
        // 往上查找从第 1 行开始找不到，需要向下查找
        $this->assertEquals('customer_photos', $this->invoke('extractTableContext', $content, 1, 15));
    }

    public function test_extract_table_context_schema_below_without_line_count(): void
    {
        // 不提供 lineCount 时，向下搜索范围为 0，找不到
        $lines = [
            '<?php',
            "Schema::create('below', function () {",
        ];
        $content = implode("\n", $lines);

        // 从第 1 行往上找不到，不提供 lineCount 时不向下搜索
        $this->assertNull($this->invoke('extractTableContext', $content, 1, 0));
    }

    // ========== extractColumnDefinitions ==========

    public function test_extract_column_definitions_basic(): void
    {
        $lines = [
            "    \$table->string('name')->comment('姓名');",
            "    \$table->integer('age')->default(0);",
        ];

        $result = $this->invoke('extractColumnDefinitions', $lines);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('age', $result);
        $this->assertCount(2, $result);
    }

    public function test_extract_column_definitions_skips_non_column_methods(): void
    {
        $lines = [
            "    \$table->index('name');",
            "    \$table->unique('email');",
            "    \$table->primary('id');",
            "    \$table->foreign('user_id');",
            "    \$table->dropColumn('old');",
            "    \$table->dropIndex('idx');",
            "    \$table->dropUnique('uniq');",
            "    \$table->dropPrimary('pk');",
            "    \$table->dropForeign('fk');",
            "    \$table->comment('表注释');",
            "    \$table->string('real_column');",
        ];

        $result = $this->invoke('extractColumnDefinitions', $lines);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('real_column', $result);
    }

    public function test_extract_column_definitions_empty_input(): void
    {
        $this->assertEmpty($this->invoke('extractColumnDefinitions', []));
    }

    public function test_extract_column_definitions_no_table_statements(): void
    {
        $lines = ['// comment', '    return true;', ''];
        $this->assertEmpty($this->invoke('extractColumnDefinitions', $lines));
    }

    // ========== classifyColumnChanges ==========

    public function test_classify_added_columns(): void
    {
        $added = [
            "    \$table->string('email')->nullable();",
            "    \$table->string('phone');",
        ];

        $changes = $this->invoke('classifyColumnChanges', $added, []);
        $this->assertCount(2, $changes);
        $this->assertEquals('add', $changes[0]['action']);
        $this->assertEquals('email', $changes[0]['column']);
        $this->assertEquals('add', $changes[1]['action']);
        $this->assertEquals('phone', $changes[1]['column']);
    }

    public function test_classify_dropped_columns(): void
    {
        $removed = [
            "    \$table->string('old_field');",
        ];

        $changes = $this->invoke('classifyColumnChanges', [], $removed);
        $this->assertCount(1, $changes);
        $this->assertEquals('drop', $changes[0]['action']);
        $this->assertEquals('old_field', $changes[0]['column']);
    }

    public function test_classify_replaced_column(): void
    {
        $added = ["    \$table->text('description')->nullable();"];
        $removed = ["    \$table->string('description')->nullable();"];

        $changes = $this->invoke('classifyColumnChanges', $added, $removed);
        $this->assertCount(1, $changes);
        $this->assertEquals('replace', $changes[0]['action']);
        $this->assertEquals('description', $changes[0]['column']);
        $this->assertStringContainsString('text', $changes[0]['definition']);
        $this->assertStringContainsString('string', $changes[0]['old_definition']);
    }

    public function test_classify_comment_only_change(): void
    {
        $added = ["    \$table->string('name')->comment('新注释');"];
        $removed = ["    \$table->string('name')->comment('旧注释');"];

        $changes = $this->invoke('classifyColumnChanges', $added, $removed);
        $this->assertCount(1, $changes);
        $this->assertEquals('comment', $changes[0]['action']);
        $this->assertEquals('name', $changes[0]['column']);
    }

    public function test_classify_mixed_changes(): void
    {
        $added = [
            "    \$table->string('new_col');",
            "    \$table->text('changed_col');",
        ];
        $removed = [
            "    \$table->string('changed_col');",
            "    \$table->string('deleted_col');",
        ];

        $changes = $this->invoke('classifyColumnChanges', $added, $removed);

        $actions = [];
        foreach ($changes as $c) {
            $actions[$c['column']] = $c['action'];
        }
        $this->assertEquals('add', $actions['new_col']);
        $this->assertEquals('drop', $actions['deleted_col']);
        $this->assertEquals('replace', $actions['changed_col']);
    }

    public function test_classify_empty_both_sides(): void
    {
        $this->assertEmpty($this->invoke('classifyColumnChanges', [], []));
    }

    // ========== normalizeDefinition ==========

    public function test_normalize_definition_removes_comment(): void
    {
        $def = "\$table->string('name')->comment('姓名')->nullable();";
        $result = $this->invoke('normalizeDefinition', $def);
        $this->assertStringNotContainsString('comment', $result);
        $this->assertStringContainsString('nullable', $result);
    }

    public function test_normalize_definition_no_comment(): void
    {
        $def = "\$table->string('name')->nullable();";
        $this->assertEquals($def, $this->invoke('normalizeDefinition', $def));
    }

    public function test_normalize_definition_comment_with_chinese(): void
    {
        $def = "\$table->string('name')->comment('中文注释');";
        $result = $this->invoke('normalizeDefinition', $def);
        $this->assertStringNotContainsString('中文注释', $result);
    }

    // ========== isOnlyCommentChange ==========

    public function test_is_only_comment_change_identical(): void
    {
        $a = "\$table->string('name')->nullable()";
        $this->assertTrue($this->invoke('isOnlyCommentChange', $a, $a));
    }

    public function test_is_only_comment_change_trailing_semicolons(): void
    {
        $a = "\$table->string('name')->nullable();";
        $b = "\$table->string('name')->nullable()";
        $this->assertTrue($this->invoke('isOnlyCommentChange', $a, $b));
    }

    public function test_is_only_comment_change_different_type(): void
    {
        $a = "\$table->text('name')->nullable()";
        $b = "\$table->string('name')->nullable()";
        $this->assertFalse($this->invoke('isOnlyCommentChange', $a, $b));
    }

    // ========== buildTableModification ==========

    public function test_build_modification_add_column(): void
    {
        $changes = [
            ['action' => 'add', 'column' => 'email', 'definition' => "\$table->string('email')->nullable()"],
        ];

        $result = $this->invoke('buildTableModification', 'users', $changes);
        $this->assertStringContainsString("Schema::table('users'", $result);
        $this->assertStringContainsString("\$table->string('email')", $result);
    }

    public function test_build_modification_drop_column(): void
    {
        $changes = [
            ['action' => 'drop', 'column' => 'old_field', 'definition' => "\$table->string('old_field')"],
        ];

        $result = $this->invoke('buildTableModification', 'users', $changes);
        $this->assertStringContainsString("dropColumn('old_field')", $result);
    }

    public function test_build_modification_replace_generates_drop_and_add(): void
    {
        $changes = [
            [
                'action' => 'replace',
                'column' => 'desc',
                'definition' => "\$table->text('desc')",
                'old_definition' => "\$table->string('desc')",
            ],
        ];

        $result = $this->invoke('buildTableModification', 'posts', $changes);
        $this->assertStringContainsString("dropColumn('desc')", $result);
        $this->assertStringContainsString('TODO: Review', $result);
        $this->assertStringContainsString("\$table->text('desc')", $result);
        // 应该有两个 Schema::table 块（一个 drop、一个 add）
        $this->assertEquals(2, substr_count($result, "Schema::table('posts'"));
    }

    public function test_build_modification_comment_change(): void
    {
        $changes = [
            [
                'action' => 'comment',
                'column' => 'status',
                'definition' => "\$table->tinyInteger('status')->comment('新注释')",
                'old_definition' => "\$table->tinyInteger('status')->comment('旧注释')",
            ],
        ];

        $result = $this->invoke('buildTableModification', 'tasks', $changes);
        $this->assertStringContainsString("Schema::table('tasks'", $result);
        $this->assertStringContainsString("->comment('新注释')", $result);
        $this->assertStringContainsString('->change()', $result);
        // 不应有 TODO 注释
        $this->assertStringNotContainsString('TODO', $result);
        // 不应有注释掉的代码
        $this->assertStringNotContainsString('// $table', $result);
    }

    public function test_build_modification_empty_returns_null(): void
    {
        $this->assertNull($this->invoke('buildTableModification', 'users', []));
    }

    public function test_build_modification_multiple_adds(): void
    {
        $changes = [
            ['action' => 'add', 'column' => 'a', 'definition' => "\$table->string('a')"],
            ['action' => 'add', 'column' => 'b', 'definition' => "\$table->integer('b')"],
        ];

        $result = $this->invoke('buildTableModification', 't', $changes);
        $this->assertStringContainsString("\$table->string('a')", $result);
        $this->assertStringContainsString("\$table->integer('b')", $result);
        // 应在同一个 Schema::table 块中
        $this->assertEquals(1, substr_count($result, "Schema::table('t'"));
    }

    // ========== mergeTableOperations ==========

    public function test_merge_same_table(): void
    {
        $operations = [
            ['type' => 'modify', 'code' => 'code_a', 'table' => 'users'],
            ['type' => 'modify', 'code' => 'code_b', 'table' => 'users'],
        ];

        $result = $this->invoke('mergeTableOperations', $operations);
        $this->assertCount(1, $result);
        $this->assertStringContainsString('code_a', $result[0]['code']);
        $this->assertStringContainsString('code_b', $result[0]['code']);
    }

    public function test_merge_different_tables(): void
    {
        $operations = [
            ['type' => 'modify', 'code' => 'code_a', 'table' => 'users'],
            ['type' => 'modify', 'code' => 'code_b', 'table' => 'orders'],
        ];

        $result = $this->invoke('mergeTableOperations', $operations);
        $this->assertCount(2, $result);
    }

    public function test_merge_empty(): void
    {
        $this->assertEmpty($this->invoke('mergeTableOperations', []));
    }

    public function test_merge_three_ops_same_table(): void
    {
        $operations = [
            ['type' => 'modify', 'code' => 'A', 'table' => 'x'],
            ['type' => 'modify', 'code' => 'B', 'table' => 'x'],
            ['type' => 'modify', 'code' => 'C', 'table' => 'x'],
        ];

        $result = $this->invoke('mergeTableOperations', $operations);
        $this->assertCount(1, $result);
        $this->assertStringContainsString('A', $result[0]['code']);
        $this->assertStringContainsString('B', $result[0]['code']);
        $this->assertStringContainsString('C', $result[0]['code']);
    }

    // ========== buildUpgradeCode ==========

    public function test_build_code_minimal_no_schema(): void
    {
        $code = $this->invoke('buildUpgradeCode', 'Version200', '2.0.0', ['tenant' => [], 'admin' => []]);

        $this->assertStringContainsString('class Version200 extends BaseVersion', $code);
        $this->assertStringContainsString("return '2.0.0'", $code);
        // 不再生成 updateHisVersion（由命令自动管理）
        $this->assertStringNotContainsString('updateHisVersion', $code);
        // 不再引入 Tenancy 和 AdminParameter
        $this->assertStringNotContainsString('use Stancl\Tenancy\Facades\Tenancy', $code);
        $this->assertStringNotContainsString('use App\Models\Admin\AdminParameter', $code);
        // 应包含 globalUp TODO 占位
        $this->assertStringContainsString('globalUp', $code);
        // 无 Schema 操作时不导入 Blueprint/Schema
        $this->assertStringNotContainsString('use Illuminate\Database\Schema\Blueprint', $code);
        $this->assertStringNotContainsString('use Illuminate\Support\Facades\Schema', $code);
    }

    public function test_build_code_with_tenant_ops(): void
    {
        $ops = [
            'tenant' => [
                ['type' => 'create', 'code' => "Schema::create('items', function (Blueprint \$table) {\n    \$table->id();\n})", 'table' => 'items'],
            ],
            'admin' => [],
        ];

        $code = $this->invoke('buildUpgradeCode', 'Version103', '1.0.3', $ops);

        $this->assertStringContainsString('use Illuminate\Database\Schema\Blueprint;', $code);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Schema;', $code);
        $this->assertStringContainsString('创建表 items', $code);
        // 应使用 createTableIfNotExists 替代 Schema::create
        $this->assertStringContainsString('createTableIfNotExists', $code);
        // 应在 tenantUp 方法内
        $this->assertStringContainsString('public function tenantUp()', $code);
        // 应使用 tenantInfo 而非 $tenantTag
        $this->assertStringContainsString('tenantInfo', $code);
        $this->assertStringNotContainsString('$tenantTag', $code);
        // 不应包含 centralUp 非空方法体（无 admin 操作）
        $this->assertStringNotContainsString('centralUp', $code);
    }

    public function test_build_code_with_admin_ops_in_central_up(): void
    {
        $ops = [
            'tenant' => [],
            'admin' => [
                ['type' => 'create', 'code' => "Schema::create('settings', function (Blueprint \$table) {\n    \$table->id();\n})", 'table' => 'settings'],
            ],
        ];

        $code = $this->invoke('buildUpgradeCode', 'Version103', '1.0.3', $ops);

        // admin 操作应在 centralUp 方法内，不再用 Tenancy::central 包裹
        $this->assertStringContainsString('public function centralUp()', $code);
        $this->assertStringNotContainsString('Tenancy::central', $code);
        $this->assertStringContainsString("Schema::create('settings'", $code);
    }

    public function test_build_code_with_both_tenant_and_admin(): void
    {
        $ops = [
            'tenant' => [
                ['type' => 'create', 'code' => "Schema::create('orders', function (Blueprint \$table) {\n    \$table->id();\n})", 'table' => 'orders'],
            ],
            'admin' => [
                ['type' => 'modify', 'code' => "Schema::table('config', function (Blueprint \$table) {\n    \$table->string('key');\n})", 'table' => 'config'],
            ],
        ];

        $code = $this->invoke('buildUpgradeCode', 'Version110', '1.1.0', $ops);

        // tenant 操作在 tenantUp 中
        $this->assertStringContainsString('public function tenantUp()', $code);
        $this->assertStringContainsString('创建表 orders', $code);
        // admin 操作在 centralUp 中，不需要 Tenancy::central 包裹
        $this->assertStringContainsString('public function centralUp()', $code);
        $this->assertStringContainsString('修改表 config', $code);
        $this->assertStringNotContainsString('Tenancy::central', $code);
    }

    public function test_build_code_generates_valid_php_syntax(): void
    {
        $ops = [
            'tenant' => [
                ['type' => 'create', 'code' => "Schema::create('test', function (Blueprint \$table) {\n            \$table->id();\n        })", 'table' => 'test'],
            ],
            'admin' => [
                ['type' => 'modify', 'code' => "Schema::table('cfg', function (Blueprint \$table) {\n            \$table->string('k');\n        })", 'table' => 'cfg'],
            ],
        ];

        $code = $this->invoke('buildUpgradeCode', 'Version999', '9.9.9', $ops);

        $tmpFile = tempnam(sys_get_temp_dir(), 'upgrade_test_');
        file_put_contents($tmpFile, $code);
        exec('php -l '.escapeshellarg($tmpFile).' 2>&1', $output, $exitCode);
        unlink($tmpFile);

        $this->assertEquals(0, $exitCode, "Generated PHP has syntax errors:\n".implode("\n", $output));
    }

    public function test_build_code_uses_are_sorted(): void
    {
        $ops = ['tenant' => [
            ['type' => 'create', 'code' => "Schema::create('t', function (Blueprint \$t) {})", 'table' => 't'],
        ], 'admin' => []];

        $code = $this->invoke('buildUpgradeCode', 'Version100', '1.0.0', $ops);

        $blueprintPos = strpos($code, 'use Illuminate\Database\Schema\Blueprint');
        $schemaPos = strpos($code, 'use Illuminate\Support\Facades\Schema');

        $this->assertNotFalse($blueprintPos);
        $this->assertNotFalse($schemaPos);
        $this->assertLessThan($schemaPos, $blueprintPos);

        // 不应包含旧的 use 语句
        $this->assertStringNotContainsString('use App\Models\Admin\AdminParameter', $code);
        $this->assertStringNotContainsString('use Stancl\Tenancy\Facades\Tenancy', $code);
    }

    // ========== extractTableNamesFromContent ==========

    public function test_extract_table_names_single_table(): void
    {
        $content = <<<'PHP'
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        PHP;

        $result = $this->invoke('extractTableNamesFromContent', $content);
        $this->assertEquals(['users'], $result);
    }

    public function test_extract_table_names_multiple_tables(): void
    {
        $content = <<<'PHP'
        Schema::create('orders', function ($t) { $t->id(); });
        Schema::create('order_items', function ($t) { $t->id(); });
        Schema::create('order_logs', function ($t) { $t->id(); });
        PHP;

        $result = $this->invoke('extractTableNamesFromContent', $content);
        $this->assertEquals(['orders', 'order_items', 'order_logs'], $result);
    }

    public function test_extract_table_names_no_schema(): void
    {
        $content = "<?php\n// empty migration\n";
        $result = $this->invoke('extractTableNamesFromContent', $content);
        $this->assertEmpty($result);
    }

    public function test_extract_table_names_ignores_schema_table(): void
    {
        $content = <<<'PHP'
        Schema::create('users', function ($t) { $t->id(); });
        Schema::table('users', function ($t) { $t->string('name'); });
        PHP;

        $result = $this->invoke('extractTableNamesFromContent', $content);
        // 只提取 Schema::create，不提取 Schema::table
        $this->assertEquals(['users'], $result);
    }

    // ========== parseDeletedMigration ==========

    public function test_parse_deleted_migration_generates_drop_with_todo(): void
    {
        // 使用实际存在的已删除文件进行测试
        $result = $this->invoke('parseDeletedMigration', 'v1.0.2', 'database/migrations/tenant/2024_06_27_064313_create_customer_sops_table.php');

        $this->assertNotEmpty($result);

        // 每个结果都应该是 drop 类型
        foreach ($result as $op) {
            $this->assertEquals('drop', $op['type']);
            $this->assertStringContainsString('TODO: Review', $op['code']);
            $this->assertStringContainsString('Schema::dropIfExists', $op['code']);
            $this->assertStringContainsString($op['table'], $op['code']);
        }

        // 验证提取到了 customer_sops 表
        $tables = array_column($result, 'table');
        $this->assertContains('customer_sops', $tables);
    }

    public function test_parse_deleted_migration_empty_content_returns_empty(): void
    {
        // 不存在的文件路径，git show 返回空
        $result = $this->invoke('parseDeletedMigration', 'v1.0.2', 'database/migrations/tenant/nonexistent.php');
        $this->assertEmpty($result);
    }

    // ========== buildUpgradeCode with drop ops ==========

    public function test_build_code_with_drop_ops(): void
    {
        $ops = [
            'tenant' => [
                [
                    'type' => 'drop',
                    'code' => "// TODO: Review - 确认是否需要删除表 old_table\n        Schema::dropIfExists('old_table')",
                    'table' => 'old_table',
                ],
            ],
            'admin' => [],
        ];

        $code = $this->invoke('buildUpgradeCode', 'Version200', '2.0.0', $ops);

        $this->assertStringContainsString('删除表 old_table', $code);
        $this->assertStringContainsString("Schema::dropIfExists('old_table')", $code);
        $this->assertStringContainsString('TODO: Review', $code);
    }

    public function test_build_code_with_admin_drop_in_central_up(): void
    {
        $ops = [
            'tenant' => [],
            'admin' => [
                [
                    'type' => 'drop',
                    'code' => "// TODO: Review - 确认是否需要删除表 admin_old\n        Schema::dropIfExists('admin_old')",
                    'table' => 'admin_old',
                ],
            ],
        ];

        $code = $this->invoke('buildUpgradeCode', 'Version200', '2.0.0', $ops);

        // admin 操作在 centralUp 中，不再用 Tenancy::central 包裹
        $this->assertStringContainsString('public function centralUp()', $code);
        $this->assertStringNotContainsString('Tenancy::central', $code);
        $this->assertStringContainsString('删除表 admin_old', $code);
        $this->assertStringContainsString("Schema::dropIfExists('admin_old')", $code);
    }

    public function test_build_code_with_drop_generates_valid_php(): void
    {
        $ops = [
            'tenant' => [
                ['type' => 'create', 'code' => "Schema::create('new_t', function (Blueprint \$table) {\n            \$table->id();\n        })", 'table' => 'new_t'],
                ['type' => 'drop', 'code' => "// TODO: Review\n        Schema::dropIfExists('old_t')", 'table' => 'old_t'],
            ],
            'admin' => [
                ['type' => 'drop', 'code' => "// TODO: Review\n        Schema::dropIfExists('admin_old')", 'table' => 'admin_old'],
            ],
        ];

        $code = $this->invoke('buildUpgradeCode', 'Version300', '3.0.0', $ops);

        $tmpFile = tempnam(sys_get_temp_dir(), 'upgrade_test_');
        file_put_contents($tmpFile, $code);
        exec('php -l '.escapeshellarg($tmpFile).' 2>&1', $output, $exitCode);
        unlink($tmpFile);

        $this->assertEquals(0, $exitCode, "Generated PHP has syntax errors:\n".implode("\n", $output));
    }

    // ========== handle 集成测试 ==========

    public function test_handle_existing_version_class_aborts(): void
    {
        // Version102 已存在，应报错终止
        $this->artisan('app:generate-upgrade', ['--tag' => 'v1.0.1', '--dry-run' => true])
            ->expectsOutputToContain('已存在')
            ->assertFailed();
    }

    public function test_handle_dry_run_outputs_code_without_writing(): void
    {
        // 用一个不存在的版本号避免冲突
        $this->artisan('app:generate-upgrade', ['--tag' => 'v1.0.2', '--ver' => '99.0.0', '--dry-run' => true])
            ->expectsOutputToContain('dry-run')
            ->assertSuccessful();

        // 确认文件未被创建
        $this->assertFileDoesNotExist(app_path('Upgrades/Versions/Version9900.php'));
    }
}
