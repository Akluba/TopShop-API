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
}
