<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];
    protected $fillable = ['shop_name','active','primary_contact','primary_phone','primary_email','address','city','state','zip_code'];

    public function fields()
    {

    }

}