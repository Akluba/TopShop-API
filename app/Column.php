<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Column extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];
    protected $fillable = ['field_id','column_name','type','title','system'];

    public function field()
    {
    	return $this->belongsTo('App\Field');
    }

    public function options()
    {
    	return $this->hasMany('App\Option', 'source_id')->where('source_class', 'CustomFieldLogColumn');
    }

    public static function incrementColumnName($field_id)
    {
        $column_names = self::where('field_id', $field_id)->get()->map(function($item, $key) {
            return str_replace("log_field", "", $item['column_name']);
        });

        $last = $column_names->max();

        $column_name = ((is_null($last)) ? 'log_field1' : 'log_field'.($last+1));

        return $column_name;
    }

}