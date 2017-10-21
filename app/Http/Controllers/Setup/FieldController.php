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
            'category_id' => 'required',
            'source'      => 'required',
            'title'       => 'required',
            'type'        => 'required'
        ]);

        $category_id = $request->input('category_id');
        $source      = $request->input('source');
        $title       = $request->input('title');
        $type        = $request->input('type');

        $field = new Field;

        $field->category_id  = $category_id;
        $field->source_class = $source;
        $field->title        = $title;
        $field->type         = $type;

        $field->save();

        $response = [
            'msg'   => 'Field created',
            'field' => $field
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
            'msg'   => 'Updated Field',
            'field' => $field
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
            'msg'   => 'Deleted Field',
            'field' => $field
        ];

        return response()->json($response, 201);
    }
}
