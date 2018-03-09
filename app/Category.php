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

    public static function elements($source_class)
    {
    	$categories = Category::where('source_class', $source_class)
    		->where('system', null)
            ->get();

        $field_array = array();

        foreach ($categories as $category) {
            $fields = $category->fields;
            foreach($fields as $field) {
                if ($field->type !== 'log') {
                    $field_array[$field->column_name] = [
                        'type' => $field->type,
                        'title' => $field->title
                    ];
                    if (in_array($field->type, array('select','select_multiple'))) {
                        $options = $field->options->keyBy('id');
                        $field_array[$field->column_name]['options'] = $options->toArray();
                    }
                }
            }
        }

        return $field_array;
    }

}
