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
            'source' => 'required'
        ]);

        $source = $request->input('source');

        $categories = \App\Category::where('source_class', $source)->get();

        foreach ($categories as $i => $category) {
            $view_category = [
                'href'   => '/api/category/'.$category->id,
                'method' => 'GET'
            ];

            $categories[$i]['view_category'] = $view_category;
        }

        $response = [
            'msg'        => 'List of all Shop Categories',
            'categories' => $categories
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
            'source' => 'required',
            'title'  => 'required'
        ]);

        $source = $request->input('source');
        $title = $request->input('title');

        $category = new Category;

        $category->source_class = $source;
        $category->title        = $title;

        $category->save();

        $category->view_category = [
            'href'   => '/api/category/'.$category->id,
            'method' => 'GET'
        ];

        $response = [
            'msg'      => 'Category created',
            'category' => $category
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

        $category->all_categories = [
            'href' => '/api/category',
            'method' => 'GET'
        ];

        $category->fields = \App\Category::find($id)->fields;

        $response = [
            'msg'      => 'Display specific Category',
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

        $category->view_category = [
            'href'   => '/api/category/'.$category->id,
            'method' => 'GET'
        ];

        $response = [
            'msg'      => 'Updated Category',
            'category' => $category
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
            'msg'      => 'Deleted Category',
            'category' => $category
        ];

        return response()->json($response, 201);
    }
}
