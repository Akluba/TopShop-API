<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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
    	return $this->hasMany('App\Option', 'source_id')->where('source_class', 'CustomField');;
    }

    public function columns()
    {
        return $this->hasMany('App\Column', 'field_id');
    }

    public static function incrementColumnName($source_class)
    {
        $column_names = self::withTrashed()->where('source_class', $source_class)->get()->map(function($item, $key) {
            return str_replace("custom_", "", $item['column_name']);
        });

        $last = $column_names->max();

        $column_name = ((is_null($last)) ? 'custom_1' : 'custom_'.($last+1));

        return $column_name;
    }

    public static function addColumnToTable($table_name, $column_name) {
        Schema::table($table_name, function (Blueprint $table) use ($column_name) {
            $table->string($column_name)->nullable();
        });
    }
}
