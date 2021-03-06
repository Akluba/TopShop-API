<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function log_entries()
    {
    	return $this->hasMany('App\LogEntry', 'source_id')->where('source_class', 'Vendor');
    }

}