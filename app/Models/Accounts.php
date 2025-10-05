<?php

namespace App\Models;

class Accounts extends BaseModel
{
	protected $table      = 'accounts';
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
