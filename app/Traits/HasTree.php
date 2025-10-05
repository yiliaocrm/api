<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

trait HasTree
{
    protected static function bootHasTree(): void
    {
        static::created(function ($model) {
            $model->updateTreeField();
            $model->updateOrderField();
            $model->updateKeywordField();
            $model->saveQuietly(); // 静默保存，避免触发无限循环
            $model->updateParentChild($model->{static::parentidField()});
        });

        static::updated(function ($model) {
            $dirty    = $model->getDirty();
            $original = $model->getRawOriginal();

            // 变更名字
            if (isset($dirty[static::nameField()])) {
                $model->updateKeywordField();
                $model->saveQuietly();
            }

            // 移动父级分类
            if (isset($dirty[static::parentidField()])) {
                $model->updateTreeField();
                $model->saveQuietly();

                // 更新所有子节点的tree字段
                $model->updateDescendantsTree($original[static::treeField()], $model->{static::treeField()});

                // 更新新旧父节点的 child 字段
                $model->updateParentChild($model->{static::parentidField()});
                $model->updateParentChild($original[static::parentidField()]);
            }
        });

        static::deleted(function ($model) {
            $model->deleteDescendants();
            $model->updateParentChild($model->{static::parentidField()});
        });
    }

    public static function getInfo($id)
    {
        if (!$id) {
            return false;
        }

        static $_info = [];

        if (!isset($_info[$id])) {
            $_info[$id] = static::query()->find($id);
        }

        return $_info[$id];
    }

    /**
     * 自定义name字段
     * @return string
     */
    protected static function nameField(): string
    {
        return 'name';
    }

    /**
     * 自定义tree字段
     * @return string
     */
    protected static function treeField(): string
    {
        return 'tree';
    }

    /**
     * 自定义keyword字段
     * @return string
     */
    protected static function keywordField(): string
    {
        return 'keyword';
    }

    /**
     * 自定义parentid字段
     * @return string
     */
    protected static function parentidField(): string
    {
        return 'parentid';
    }

    /**
     * 自定义order字段
     * @return string
     */
    protected static function orderField(): string
    {
        return 'order';
    }

    /**
     * 更新order字段
     * @return void
     */
    public function updateOrderField(): void
    {
        $orderField = static::orderField();
        if ($orderField) {
            $this->{$orderField} = $this->{$orderField} ?? $this->id;
        }
    }

    /**
     * 更新tree字段
     * @return void
     */
    public function updateTreeField(): void
    {
        if ($this->{static::parentidField()}) {
            $parent                      = static::query()->find($this->{static::parentidField()});
            $this->{static::treeField()} = $parent->{static::treeField()} . '-' . $this->id;
        } else {
            $this->{static::treeField()} = '0-' . $this->id;
        }
    }

    /**
     * 更新父节点的 child 字段
     * @param int $parentId
     * @return void
     */
    public function updateParentChild(int $parentId): void
    {
        if ($parentId) {
            $parent        = static::query()->find($parentId);
            $hasChild      = static::query()->where(static::parentidField(), $parentId)->exists();
            $parent->child = $hasChild ? 1 : 0;
            $parent->saveQuietly();
        }
    }

    /**
     * 更新keyword字段
     * @return void
     */
    public function updateKeywordField(): void
    {
        if (static::keywordField() && function_exists('parse_pinyin')) {
            $this->{static::keywordField()} = implode(',', parse_pinyin($this->{static::nameField()}));
        }
    }

    /**
     * 更新所有子节点的tree字段
     * @param string $originalTree
     * @param string $newTree
     * @return void
     */
    public function updateDescendantsTree(string $originalTree, string $newTree): void
    {
        $treeField = static::treeField();
        static::query()
            ->where($treeField, 'like', "{$originalTree}-%")
            ->update([
                $treeField => DB::raw("CONCAT('{$newTree}-', SUBSTRING($treeField, " . (strlen($originalTree) + 2) . "))"),
            ]);
    }

    /**
     * 删除所有子节点
     * @return void
     */
    public function deleteDescendants(): void
    {
        $treeField = static::treeField();
        static::query()
            ->where($treeField, 'like', "{$this->{$treeField}}-%")
            ->delete();
    }

    /**
     * 获取所有子节点
     * @return Collection
     * @example Model::query()->find(1)->getAllChild()
     */
    public function getAllChild(): Collection
    {
        return $this
            ->where(static::treeField(), 'like', "{$this->{static::treeField()}}-%")
            ->orWhere('id', $this->id)
            ->orderBy('id')
            ->get();
    }

    /**
     * 获取所有父节点
     * @param string $glue
     * @return string
     */
    public function getFullPath(string $glue = ' > '): string
    {
        $path = [];
        $tree = explode('-', $this->{static::treeField()});
        foreach ($tree as $id) {
            $info = static::getInfo($id);
            if ($info) {
                $path[] = $info->{static::nameField()};
            }
        }
        return implode($glue, $path);
    }
}
