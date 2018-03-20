<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Company;
use App\Shop;
use App\CompanyRequirement;
use App\Field;

class CompanyRequirementsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get Shop fields.
        $fields = Field::where('source_class', 'Shop')
                    ->whereNotIn('type', ['notes', 'log'])
                    ->get();

        // Get Select Options.
        $fields->map(function ($field) {
            if (in_array($field->type, ['select','select_multiple'])) {
                $field->options;
            }
        });

        $response = [
            'message' => 'List of all Companies',
            'data'    => $fields
        ];

        return response()->json($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $company = Company::find($id);

        $company_requirements = $company->requirements;

        $response = [
            'message' => 'List of Company Requirements',
            'data'    => $company_requirements
        ];

        return response()->json($response, 200);
    }

}