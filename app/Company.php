<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $table = 'companies';
    protected $dates = ['deleted_at'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function requirements()
    {
        return $this->hasMany('App\CompanyRequirement', 'company_id');
    }

    public function log_entries()
    {
        return $this->hasMany('App\LogEntry', 'source_id')->where('source_class', 'Company');
    }
}
