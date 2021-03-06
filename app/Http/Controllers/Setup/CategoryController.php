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

        $source_class = ucfirst($request->input('source_class'));

        $categories = \App\Category::where('source_class', $source_class)->get();

        $data = [
            'ancestor' => null,
            'parent'   => null,
            'primary'  => null,
            'children' => $categories
        ];

        $response = [
            'message' => "List of all {$source_class} Categories",
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
            'source_class' => 'required',
            'title'        => 'required',
            'sort_order'   => 'required',
        ]);

        $source_class = ucfirst($request->input('source_class'));
        $title = $request->input('title');
        $sort_order = $request->input('sort_order');

        $category = new Category;

        $category->source_class = $source_class;
        $category->title = $title;
        $category->sort_order = $sort_order;

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
        $fields = \App\Category::find($id)->fields;

        $data = [
            'ancestor' => null,
            'parent'   => null,
            'primary'  => $category,
            'children' => $fields
        ];

        $response = [
            'message' => "Displaying fields for Category: {$category->title}",
            'data'    => $data
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
        if ($id == 0) {
            $this->update_sort_order($request->input('data'));
            return response()->json(['message' => 'sort order updated.'], 201);
        }

        $request->validate([
            'title'  => 'required',
            'sort_order' => 'required',
        ]);

        $title = $request->input('title');
        $sort_order = $request->input('sort_order');

        $category = \App\Category::find($id);

        $category->title = $title;
        $category->sort_order = $sort_order;
        $category->save();

        $response = [
            'message' => "Category: {$category->title}, has been updated.",
            'data'    => $category
        ];

        return response()->json($response, 201);
    }

    public function update_sort_order(array $values)
    {
        foreach ($values as $value) {
            $id = $value['id'];
            $category = \App\Category::find($id);
            $category->sort_order = $value['sort_order'];
            $category->save();
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
        $category = \App\Category::find($id);

        $category->delete();

        $response = [
            'message' => "Category: {$category->title}, has been deleted.",
            'data'    => $category
        ];

        return response()->json($response, 200);
    }
}
