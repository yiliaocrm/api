<?php

namespace App\Models;




/**
 * 生产厂家
 */
class Manufacturer extends BaseModel
{
	protected $table      = 'manufacturer';
	protected $guarded    = [];
	protected $primaryKey = 'id';

	public static function boot()
	{
		parent::boot();

		static::saving(function($manufacturer)
		{
			$manufacturer->keyword = implode(',', parse_pinyin($manufacturer->name . $manufacturer->short_name));
		});
	}

	public static function getInfo($id, $field = '')
	{
		if(!$id) return false;
		static $_info = array();
		if(!isset($_info[$id]))
		{
			$_info[$id] = static::find($id)->toArray();
		}
		return $field ? $_info[$id][$field] : $_info[$id];
	}
}
