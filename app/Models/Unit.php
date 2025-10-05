<?php

namespace App\Models;



class Unit extends BaseModel
{
	protected $table      = 'unit';
	protected $guarded    = [];
	protected $primaryKey = 'id';

	public static function boot()
	{
		parent::boot();

		static::saving(function($unit) {
			$unit->keyword = implode(',', parse_pinyin($unit->name));
		});
	}

    public static function getInfo($id)
    {
        if (!$id) {
            return false;
        }

        static $_info = [];

        if(!isset($_info[$id])) {
            $_info[$id] = static::find($id);
        }
        
        return $_info[$id];
    }
}
