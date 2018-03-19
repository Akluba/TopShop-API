<?php

namespace App\Helpers;

use App\Http\Controllers\Controller;

class SourceClassTable extends Controller
{
	public $source_class;

	public function __construct($source_class)
    {
        $this->source_class = $source_class;
    }

    public function tableName()
    {
    	switch ($this->source_class) {
    		case 'Shop':
    			return 'shops';
    			break;
    		case 'Manager':
    			return 'managers';
    			break;
    		case 'Company':
    			return 'companies';
    			break;
    		case 'Vendor':
    			return 'vendors';
    			break;
    		case 'Cpr':
    			return 'cpr';
    			break;
    	}
    }

}