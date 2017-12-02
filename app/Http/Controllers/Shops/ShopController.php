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
            'title' => 'Primary Details',
            'fields'   => [
                ['title' => 'Shop Name', 'value' => $shop->shop_name],
                ['title' => 'Active', 'value' => $shop->active],
                ['title' => 'Contact', 'value' => $shop->primary_contact],
                ['title' => 'Phone', 'value' => $shop->primary_phone],
                ['title' => 'Email', 'value' => $shop->primary_email],
                ['title' => 'Address', 'value' => $shop->address],
                ['title' => 'City', 'value' => $shop->city],
                ['title' => 'State', 'value' => $shop->state],
                ['title' => 'Zip Code', 'value' => $shop->zip_code],
            ]
        ];

        foreach ($categories as $category) {
            $fields = $category->fields;
            $field_value_pairs = [];
            foreach($fields as $field) {
                $field_value_pairs[] = [
                    'title' => $field->title,
                    'value' => $shop[$field->column_name]
                ];
            }

            $data['categories'][] = [
                'title'  => $category->title,
                'fields' => $field_value_pairs
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
