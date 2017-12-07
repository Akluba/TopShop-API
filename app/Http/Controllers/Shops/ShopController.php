<?php

namespace App\Http\Controllers\Shops;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shop;
use App\Category;
use App\LogEntry;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
            'shop_name'       => 'required'
        ]);

        $shop_name = $request->input('shop_name');

        $shop = new Shop;
        $shop->shop_name = $shop_name;
        $shop->save();

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

        // Get all log entries for the Shop.
        $log_entries = $shop->log_entries;
        // Group entries by field id.
        $field_log_entries = $log_entries->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        // Get Shop categories / fields / field options / field columns / column options.
        $categories = \App\Category::where('source_class', 'Shop')->get();
        foreach ($categories as $category) {
            $fields = $category->fields;
            foreach($fields as $field) {
                if ($field->type !== 'log') {
                    // Get the value of the field.
                    $field->value = $shop[$field->column_name];
                    // Get select options for necessary fields.
                    if (in_array($field->type, array('select','select_multiple'))) {
                        $options = $field->options;
                    }
                }
                // Get columns for logging fields.
                else {
                    $columns = $field->columns;
                    foreach ($columns as $column) {
                        // Get select options for necessary logging fields.
                        if (in_array($column->type, array('select','select_multiple'))) {
                            $column->options;
                        }
                    }
                    // Get log entries for logging field.
                    if (!empty($field_log_entries->toArray())) {
                        $field->log_entries = $field_log_entries->get($field->id)->all();
                    }
                }
            }
        }

         $data = [
            'id'         => $shop->id,
            'shop_name'  => $shop->shop_name,
            'categories' => $categories
        ];

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
        // Get the inputs from the request.
        $inputs = $request->toArray();

        // Update log entries
        foreach ($inputs as $custom => $input) {
            if (is_array($input)) {
                $inputs[$custom] = null;
                foreach ($input as $log_entry) {
                    \App\LogEntry::where('id', $log_entry['id'])
                        ->update($log_entry);
                }
            }
        }

        // Update shop inputs
        \App\Shop::where('id', $id)
            ->update($inputs);

        $shop = \App\Shop::find($id);

        $response = [
            'message' => "{$shop->shop_name} has been updated.",
            'data'    => $shop
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
        // $category = \App\Category::find($id);

        // $category->delete();

        // $response = [
        //     'message' => "Category: {$category->title}, has been deleted.",
        //     'data'    => $category
        // ];

        // return response()->json($response, 200);
    }
}
