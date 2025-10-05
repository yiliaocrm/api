<?php

namespace App\Models;



class Diagnosis extends BaseModel
{
	protected $table      = 'diagnosis';
	protected $guarded    = [];
	protected $primaryKey = 'id';

	public static function boot()
	{
		parent::boot();

		static::saving(function($diagnosis) {
			$diagnosis->keyword = implode(',', array_filter(
				array_merge([$diagnosis->code], parse_pinyin($diagnosis->name))
			));
		});
	}
}
