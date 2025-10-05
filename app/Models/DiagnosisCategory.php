<?php

namespace App\Models;



class DiagnosisCategory extends BaseModel
{
	protected $table      = 'diagnosis_category';
	protected $guarded    = [];
	protected $primaryKey = 'id';



	public static function boot()
	{
		parent::boot();
		
		# 更新节点信息
		static::created(function($model)
		{
			if($model->parentid)
			{
				$parent = static::find($model->parentid);
				static::find($model->id)->update([
					'keyword' => implode(',', parse_pinyin($model->name)),
					'tree'    => $parent->tree.'-'.$model->id
				]);
				$parent->update(['child' => 1]);
			}
			else
			{
				static::find($model->id)->update([
					'keyword' => implode(',', parse_pinyin($model->name)),
					'tree'    => '0-'.$model->id
				]);
			}
		});

		static::updating(function($model)
		{
			$dirty = $model->getDirty();

			if(isset($dirty['name']))
			{
				$model->keyword = implode(',', parse_pinyin($model->name));
			}

			# 移动节点,更新自身tree
			if(isset($dirty['parentid']))
			{
				$parent      = static::find($model->parentid);
				$model->tree = $parent->tree.'-'.$model->id;
				$parent->update(['child' => 1]);
			}
		});

		static::updated(function($model)
		{
			$dirty    = $model->getDirty();
			$original = $model->getRawOriginal();

			# 移动节点
			if(isset($dirty['parentid']))
			{
				DB::select(DB::raw("update ".DB::getTablePrefix()."diagnosis_category set tree = CONCAT('{$model->tree}-',id) where tree like '{$original['tree']}-%'"));
				$child = static::where('parentid', $original['parentid'])->first();
				if(!$child)
				{
					static::find($original['parentid'])->update(['child' => 0]);
				}
			}
		});

		static::deleting(function($model)
		{
			$tree = $model->tree;
			static::where('tree', 'like', "{$tree}-%")->delete();
		});

        static::deleted(function($model) {
            $parent = static::find($model->parentid);
            if ($parent->getAllChild()->count() == 1) {
                $parent->update(['child' => 0]);
            }
        });
	}

	# 获取所有子节点
	public function getAllChild()
	{
		return static::where('tree', 'like', "{$this->tree}-%")->orWhere('id', $this->id)->orderBy('id', 'ASC')->get();
	}
}
