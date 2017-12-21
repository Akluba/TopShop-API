<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Field;

class FieldController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'source_class' => 'required',
            'category_id'  => 'required',
            'title'        => 'required',
            'type'         => 'required'
        ]);

        $source_class = $request->input('source_class');
        $category_id  = $request->input('category_id');
        $title        = $request->input('title');
        $type         = $request->input('type');
        $column_name  = \App\Field::incrementColumnName($source_class);

        $field = new Field;
        $field->source_class = $source_class;
        $field->category_id  = $category_id;
        $field->title        = $title;
        $field->type         = $type;
        $field->column_name  = $column_name;
        $field->save();

        $table = ($source_class === 'shop') ? 'shops' : 'managers';
        \App\Field::addColumnToTable($table, $field->column_name);

        // Add default columns for notes field.
        if ($field->type === 'notes') {
            \App\Field::storeSystemColumns($field->id);
        }

        $response = [
            'message' => "Field: {$field->title}, has been created.",
            'data'    => $field
        ];

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $field = \App\Field::find($id);
        $category = $field->category;

        if (in_array($field->type, array('log','notes'))) {
            $children = \App\Field::find($id)->columns;
        }
        else {
            $children = \App\Field::find($id)->options()->where('source_class', 'CustomField')->get();
        }

        $data = [
            'ancestor' => null,
            'parent'   => $category,
            'primary'  => $field,
            'children' => $children
        ];

        $response = [
            'message' => "Displaying options/columns for Field: {$field->title}",
            'data'    => $data
        ];

        return response()->json($response, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'  => 'required',
        ]);

        $title = $request->input('title');

        $field = \App\Field::find($id);

        $field->title = $title;
        $field->save();

        $response = [
            'message' => "Field: {$field->title}, has been updated.",
            'data'    => $field
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
        $field = \App\Field::find($id);

        $field->delete();

        $response = [
            'message' => "Field: {$field->title}, has been deleted.",
            'data'    => $field
        ];

        return response()->json($response, 200);
    }
}
