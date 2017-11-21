<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Column;

class ColumnController extends Controller
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
            'field_id' => 'required',
            'type'     => 'required',
            'title'    => 'required'
        ]);

        $field_id = $request->input('field_id');
        $type     = $request->input('type');
        $title    = $request->input('title');
        $column_name  = \App\Column::incrementColumnName($field_id);

        $column = New Column;

        $column->field_id    = $field_id;
        $column->column_name = $column_name;
        $column->type        = $type;
        $column->title       = $title;

        $column->save();

        $response = [
            'message' => "Column: {$column->title}, has been created.",
            'data'    => $column
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
        $column = \App\Column::find($id);
        $field = $column->field;
        $category = $column->field->category;

        $options = \App\Column::find($id)->options()->where('source_class', 'CustomFieldLogColumn')->get();

        $column->options = $options;

        $data = [
            'ancestor' => $category,
            'parent'   => $field,
            'primary'  => $column,
            'children' => $options
        ];

        $response = [
            'message' => "Displaying options for Column: {$column->title}",
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

        $column = \App\Column::find($id);

        $column->title = $title;
        $column->save();

        $response = [
            'message' => "Column: {$column->title}, has been updated.",
            'data'    => $column
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
        $column = \App\Column::find($id);

        $column->delete();

        $response = [
            'message' => "Column: {$column->title}, has been deleted.",
            'data'    => $column
        ];

        return response()->json($response, 200);
    }
}
