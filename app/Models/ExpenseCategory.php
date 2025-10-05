<?php

namespace App\Models;



class ExpenseCategory extends BaseModel
{
	protected $table      = 'expense_category';
	protected $guarded    = [];
	protected $primaryKey = 'id';

	public static function boot()
	{
		parent::boot();

		static::saving(function($model) {
			$model->keyword = implode(',', parse_pinyin($model->name));
		});
	}

	public static function getInfo($id)
	{
		if (!$id)
		{
			return false;
		}
		static $_info = [];
		if(!isset($_info[$id]))
		{
			$_info[$id] = static::find($id);
		}
		return $_info[$id];
	}
}
