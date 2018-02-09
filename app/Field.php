<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

use App\Column;

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
    	return $this->hasMany('App\Option', 'source_id')->where('source_class', 'CustomField');
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

    public static function storeSystemColumns($field_id)
    {
        $field = self::find($field_id);

        $field->columns()->saveMany([
            new Column(['column_name' => 'log_field1', 'type' => 'user_stamp', 'title' => 'Created By', 'sort_order' => 1, 'system' => 1]),
            new Column(['column_name' => 'log_field2', 'type' => 'date_stamp', 'title' => 'Created Date', 'sort_order' => 2, 'system' => 1]),
            new Column(['column_name' => 'log_field3', 'type' => 'note_text', 'title' => 'Message', 'sort_order' => 3, 'system' => 1]),
        ]);
    }

    public static function addColumnToTable($table_name, $column_name)
    {
        Schema::table($table_name, function (Blueprint $table) use ($column_name) {
            $table->longText($column_name)->nullable();
        });
    }

}
