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
            'type'         => 'required',
            'sort_order'   => 'required',
        ]);

        $source_class = ucfirst($request->input('source_class'));
        $category_id  = $request->input('category_id');
        $title        = $request->input('title');
        $type         = $request->input('type');
        $sort_order   = $request->input('sort_order');
        $column_name  = \App\Field::incrementColumnName($source_class);

        $field = new Field;
        $field->source_class = $source_class;
        $field->category_id  = $category_id;
        $field->title        = $title;
        $field->type         = $type;
        $field->column_name  = $column_name;
        $field->sort_order   = $sort_order;
        $field->save();

        $table = ($source_class === 'Shop') ? 'shops' : 'managers';
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
        if ($id == 0) {
            $this->update_sort_order($request->input('data'));
            return response()->json(['message' => 'sort order updated.'], 201);
        }

        $request->validate([
            'title'      => 'required',
            'sort_order' => 'required',
        ]);

        $title      = $request->input('title');
        $sort_order = $request->input('sort_order');

        $field = \App\Field::find($id);

        $field->title = $title;
        $field->sort_order = $sort_order;
        $field->save();

        $response = [
            'message' => "Field: {$field->title}, has been updated.",
            'data'    => $field
        ];

        return response()->json($response, 201);
    }

    public function update_sort_order(array $values)
    {
        foreach ($values as $value) {
            $id = $value['id'];
            $field = \App\Field::find($id);
            $field->sort_order = $value['sort_order'];
            $field->save();
        }
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
