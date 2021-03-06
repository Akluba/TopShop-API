<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function fields()
    {
    	return $this->hasMany('App\Field');
    }

}
