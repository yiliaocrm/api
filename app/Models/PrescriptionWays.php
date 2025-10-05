<?php

namespace App\Models;



class PrescriptionWays extends BaseModel
{
	protected $table      = 'prescription_ways';
	protected $guarded    = [];
	protected $primaryKey = 'id';

	public static function boot()
	{
		parent::boot();

		static::saving(function($ways) {
			$ways->keyword  = implode(',', parse_pinyin($ways->name));
		});
	}
}
