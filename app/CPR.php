<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CPR extends Model
{
    use SoftDeletes;

    protected $table = 'cpr';
    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function log_entries()
    {
    	return $this->hasMany('App\LogEntry', 'source_id')->where('source_class', 'Cpr');
    }

}