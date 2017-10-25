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
            'label'        => 'required'
        ]);

        $source_class = $request->input('source_class');
        $source_id    = $request->input('source_id');
        $label        = $request->input('label');

        $option = New Option;

        $option->source_class = $source_class;
        $option->source_id    = $source_id;
        $option->label        = $label;

        $option->save();

        $option->actions = [
            'href'   => '/api/option/'.$option->id,
            'method' => [
                'update'  => 'PUT',
                'destroy' => 'DELETE'
            ]
        ];

        $response = [
            'msg'    => 'Option created',
            'option' => $option
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
            'label'  => 'required',
        ]);

        $label = $request->input('label');

        $option = \App\Option::find($id);

        $option->label = $label;
        $option->save();

        $response = [
            'msg'    => 'Updated Option',
            'option' => $option
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
            'msg'    => 'Deleted Option',
            'option' => $option
        ];

        return response()->json($response, 201);
    }
}
