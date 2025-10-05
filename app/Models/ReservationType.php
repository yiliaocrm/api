<?php

namespace App\Models;



class ReservationType extends BaseModel
{
	protected $table      = 'reservation_type';
	protected $guarded    = [];
	protected $primaryKey = 'id';

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
