<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'source_class' => 'required'
        ]);

        $source_class = $request->input('source_class');

        $actions = [
            'store' => [
                'href'   => '/api/category?source_class='.$source_class,
                'method' => 'POST'
            ]
        ];

        $categories = \App\Category::where('source_class', $source_class)->get();

        $response = [
            'message' => 'List of all Shop Categories',
            'data'    => $categories
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
            'source_class' => 'required',
            'title'        => 'required'
        ]);

        $source_class = $request->input('source_class');
        $title        = $request->input('title');

        $category = new Category;

        $category->source_class = $source_class;
        $category->title        = $title;

        $category->save();

        $response = [
            'message' => "Category: {$category->title}, has been created.",
            'data'    => $category
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
        $category = \App\Category::find($id);

        $actions = [
            'back'  => [
                'href' => '/api/category?source_class='.$category->source_class,
                'method' => 'GET'
            ],
            'store' => [
                'href' => '/api/field?source_class='.$category->source_class.'&category_id='.$category->id,
                'method' => 'POST'
            ]
        ];

        $fields = \App\Category::find($id)->fields;

        foreach($fields as $i => $field) {
            $field_actions = [
                'href' => '/api/field/'.$field->id,
                'method' => [
                    'update'  => 'PUT',
                    'destroy' => 'DELETE'
                ]
            ];

            if (in_array($field->type, array('log','select','select_multiple'))) {
                $field_actions['method']['show'] = 'GET';
            }

            $fields[$i]['actions'] = $field_actions;
        }

        $category->fields = $fields;

        $response = [
            'msg'      => 'Display specific Category',
            'actions'  => $actions,
            'category' => $category
        ];

        return response()->json($response, 200);
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

        $category = \App\Category::find($id);

        $category->title = $title;
        $category->save();

        $response = [
            'message' => "Category: {$category->title}, has been updated.",
            'data'    => $category
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
        $category = \App\Category::find($id);

        $category->delete();

        $response = [
            'message' => "Category: {$category->title}, has been deleted.",
            'data'    => $category
        ];

        return response()->json($response, 200);
    }
}
