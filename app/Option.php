<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Option extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function field()
    {
    	return $this->belongsTo('App\Field');
    }

    public function column()
    {
    	return $this->belongsTo('App\Column');
    }
}
