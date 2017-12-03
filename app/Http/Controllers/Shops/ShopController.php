<?php

namespace App\Http\Controllers\Shops;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shop;
use App\Category;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $shops = \App\Shop::all();

        $response = [
            'message' => 'List of all Active Shops',
            'data'    => $shops
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
            'shop_name'       => 'required',
            'primary_contact' => 'required',
            'primary_phone'   => 'required',
            'primary_email'   => 'required',
            'address'         => 'required',
            'city'            => 'required',
            'state'           => 'required',
            'zip_code'        => 'required',
        ]);

        $data = json_decode($request->getContent(), true);
        $shop = \App\Shop::create($data);

        $response = [
            'message' => "Shop: {$shop->shop_name}, has been created.",
            'data'    => $shop
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
        $shop = \App\Shop::find($id);
        $categories = \App\Category::where('source_class', 'Shop')->get();

        $data = [
            'shop_name'  => $shop->shop_name,
            'categories' => []
        ];

        $data['categories'][] = [
            'category' => 'Primary Details',
            'fields'   => [
                ['title' => 'Shop Name', 'value' => $shop->shop_name, 'column_name' => 'shop_name', 'type' => 'text'],
                ['title' => 'Active', 'value' => $shop->active, 'column_name' => 'active', 'type' => 'checkbox'],
                ['title' => 'Contact', 'value' => $shop->primary_contact, 'column_name' => 'primary_contact', 'type' => 'text'],
                ['title' => 'Phone', 'value' => $shop->primary_phone, 'column_name' => 'primary_phone', 'type' => 'text'],
                ['title' => 'Email', 'value' => $shop->primary_email, 'column_name' => 'primary_email', 'type' => 'text'],
                ['title' => 'Address', 'value' => $shop->address, 'column_name' => 'address', 'type' => 'text'],
                ['title' => 'City', 'value' => $shop->city, 'column_name' => 'city', 'type' => 'text'],
                ['title' => 'State', 'value' => $shop->state, 'column_name' => 'state', 'type' => 'text'],
                ['title' => 'Zip Code', 'value' => $shop->zip_code, 'column_name' => 'zip_code', 'type' => 'text'],
            ]
        ];

        foreach ($categories as $category) {
            $fields = [];
            foreach($category->fields as $field) {
                $field_object = [
                    'value'       => $shop[$field->column_name],
                    'column_name' => $field->column_name,
                    'title'       => $field->title,
                    'type'        => $field->type,
                ];

                // Get select options for necessary fields.
                if (in_array($field->type, array('select','select_multiple'))) {
                    foreach($field->options as $option) {
                        $field_object['options'][] = [
                            'id'    => $option->id,
                            'title' => $option->title
                        ];
                    }
                }

                $fields[] = $field_object;
            }

            $data['categories'][] = [
                'category' => $category->title,
                'fields'   => $fields
            ];
        }

        $response = [
            'message' => "Displaying shop details for: {$shop->shop_name}",
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
        // $request->validate([
        //     'title'  => 'required',
        // ]);

        // $title = $request->input('title');

        // $category = \App\Category::find($id);

        // $category->title = $title;
        // $category->save();

        // $response = [
        //     'message' => "Category: {$category->title}, has been updated.",
        //     'data'    => $category
        // ];

        // return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // $category = \App\Category::find($id);

        // $category->delete();

        // $response = [
        //     'message' => "Category: {$category->title}, has been deleted.",
        //     'data'    => $category
        // ];

        // return response()->json($response, 200);
    }
}
