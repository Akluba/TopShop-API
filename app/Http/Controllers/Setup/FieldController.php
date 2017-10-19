<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
            'source' => 'required',
            'title'  => 'required',
            'type'   => 'required'
        ]);

        $source = $request->input('source');
        $title = $request->input('title');
        $type = $request->input('type');

        // INSERT

        $field = [
            'source'     => $source,
            'title'      => $title,
            'type'       => $type,
            'view_field' => [
                'href'   => '/api/field/{$id}',
                'method' => 'GET'
            ]
        ];

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
            'title'  => 'required'
        ]);

        $title = $request->input('title');

        // UPDATE

        $field = [
            'id'    => $id,
            'title' => $title
        ];

        $response = [
            'msg'   => 'Field updated',
            'field' => $field
        ];

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // DELETE

        $field = [
            'id'    => $id
        ];

        $response = [
            'msg'   => 'Field Deleted',
            'field' => $field
        ];

        return response()->json($response, 201);
    }
}
