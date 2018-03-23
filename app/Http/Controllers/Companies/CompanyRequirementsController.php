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
    public function index(Request $request)
    {
        $request->validate([
            'company_id' => 'required'
        ]);

        $company_id = $request->input('company_id');

        $company = Company::find($company_id);

        $company_requirements = $company->requirements;

        // Get Shop fields.
        $fields = Field::where('source_class', 'Shop')
                    ->whereIn('type', ['text', 'checkbox','select','select_multiple'])
                    ->get();

        // Get Select Options.
        $fields->map(function ($field) {
            if (in_array($field->type, ['select','select_multiple'])) {
                $field->options;
            }
        });

        $data = [
            'fields'       => $fields,
            'requirements' => $company_requirements
        ];

        $response = [
            'message' => 'List of all Companies',
            'data'    => $data
        ];

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'field_id'   => 'required',
            'condition'  => 'required',
            'value'      => 'required'
        ]);

        $company_id = $request->input('company_id');
        $field_id = $request->input('field_id');
        $condition = $request->input('condition');
        $value = $request->input('value');

        $company_requirement = new CompanyRequirement;
        $company_requirement->company_id = $company_id;
        $company_requirement->field_id = $field_id;
        $company_requirement->condition = $condition;
        $company_requirement->value = $value;
        $company_requirement->save();

        $response = [
            'message' => "Company Requirement: has been added.",
            'data'    => $company_requirement
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $requirement = CompanyRequirement::find($id);

        $requirement->delete();

        $response = [
            'message' => "Requirement has been deleted.",
            'data'    => $requirement
        ];

        return response()->json($response, 200);
    }

}