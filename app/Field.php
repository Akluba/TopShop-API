<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
	use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function category()
    {
    	return $this->belongsTo('App\Category');
    }

    public function options()
    {
    	return $this->hasMany('App\Option', 'source_id');
    }

    public function columns()
    {
        return $this->hasMany('App\Column', 'field_id');
    }

    public static function incrementColumnName()
    {
        $last = self::withTrashed()->max('column_name');

        $column_name = (($last === "") ? 'custom_1' : ++$last);

        return $column_name;
    }
}
