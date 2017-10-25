<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Column extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function field()
    {
    	return $this->belongsTo('App\Field');
    }

    public function options()
    {
    	return $this->hasMany('App\Option', 'source_id');
    }

    public static function incrementColumnName($field_id)
    {
        $last = self::where('field_id', $field_id)->max('column_name');

        $column_name = (($last === "") ? 'LOG_FIELD1' : ++$last);

        return $column_name;
    }
}
