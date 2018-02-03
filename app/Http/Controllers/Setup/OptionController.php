<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Option;

class OptionController extends Controller
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
            'source_id'    => 'required',
            'title'        => 'required',
            'sort_order' => 'required',
        ]);

        $source_class = $request->input('source_class');
        $source_id    = $request->input('source_id');
        $title        = $request->input('title');
        $sort_order   = $request->input('sort_order');

        $option = New Option;

        $option->source_class = $source_class;
        $option->source_id    = $source_id;
        $option->title        = $title;
        $option->sort_order   = $sort_order;

        $option->save();

        $response = [
            'message' => "Option: {$option->title}, has been created.",
            'data'    => $option
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
            'sort_order' => 'required',
        ]);

        $title = $request->input('title');
        $sort_order = $request->input('sort_order');

        $option = \App\Option::find($id);

        $option->title = $title;
        $option->sort_order = $sort_order;
        $option->save();

        $response = [
            'message' => "Option: {$option->title}, has been updated.",
            'data'    => $option
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
        $option = \App\Option::find($id);

        $option->delete();

        $response = [
            'message' => "Option: {$option->title}, has been deleted.",
            'data'    => $option
        ];

        return response()->json($response, 200);
    }
}
