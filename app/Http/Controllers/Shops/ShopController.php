<?php

namespace App\Http\Controllers\Shops;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Shop;
use App\Category;
use App\User;
use App\LogEntry;

class ShopController extends Controller
{
    private $users;

    public function __construct()
    {
        $this->users = \App\User::all();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shops = \App\Shop::all();

        // Get Shop categories / fields / field options / field columns / column options.
        $categories = \App\Category::where('source_class', 'Shop')
            // ->where('system','!=',1)
            // ->orWhereNull('system')
            ->where('system', null)
            ->get();

        $field_array = array();

        foreach ($categories as $category) {
            $fields = $category->fields;
            foreach($fields as $field) {
                if ($field->type !== 'log') {
                    $field_array[$field->column_name] = [
                        'type' => $field->type,
                        'title' => $field->title
                    ];
                    if (in_array($field->type, array('select','select_multiple'))) {
                        $options = $field->options->keyBy('id');
                        $field_array[$field->column_name]['options'] = $options;
                    }
                }
            }
        }

        $shops->map(function ($shop) use ($field_array){
            foreach ($field_array as $custom => $field) {
                if (!is_null($shop[$custom]) && in_array($field['type'], array('select','select_multiple'))) {
                    if ($field['type'] === 'select_multiple') {
                        $option_array = array();
                        foreach (json_decode($shop[$custom]) as $option) {
                            $option_array[] = $field['options'][$option]['title'];
                        }
                        $option_string = implode(", ", $option_array);
                        $shop[$custom] = $option_string;
                    } else {
                        $shop[$custom] = $field['options'][$shop[$custom]]['title'];
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $shop[$custom] = $shop[$custom] ? 'true' : 'false';
                }
            }

            return $shop;
        });


        $data = [
            'shop_list' => $shops,
            'fields' => $field_array
        ];

        $response = [
            'message' => 'List of all Active Shops',
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

        // Group log entries by field id.
        $log_entries = $shop->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        // Get Shop categories / fields / field options / field columns / column options.
        $categories = \App\Category::where('source_class', 'Shop')->get();
        foreach ($categories as $category) {
            $fields = $category->fields;
            foreach($fields as $field) {
                if (in_array($field->type, array('select','select_multiple'))) {
                    $field->options;
                }
                elseif (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to shop object.
                    if (!empty($log_entries->get($field->id))) {
                        $field_log_entries = $log_entries->get($field->id)->all();

                        if ($field->type === 'notes') {
                            foreach ($field_log_entries as $index => $note) {
                                $field_log_entries[$index]['log_field1'] = $this->userIdToName($note['log_field1']);
                            }
                        }

                        $shop[$field->column_name] = $field_log_entries;
                    }
                    // Get select options for field.
                    foreach($field->columns as $column) {
                        if (in_array($column->type, array('select','select_multiple'))) {
                            $column->options;
                        }
                        elseif (in_array($column->type, array('manager_link','shop_link'))) {
                            $links = ($column->type === 'manager_link') ? \App\Manager::all() : \App\Shop::all();
                            $column['options'] = $links;
                        }
                    }
                }
            }
        }

        unset($shop->log_entries);

         $data = [
            'shop'            => $shop,
            'form_elements'   => $categories
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
        unset($inputs['_method']);

        // Update log entries
        $categories = \App\Category::where('source_class', 'Shop')->get();
        foreach ($categories as $category) {
            foreach($category->fields as $field) {
                if (in_array($field->type, array('log','notes'))) {
                    foreach ($inputs[$field->column_name] as $log_entry) {
                        if ($log_entry['id'] === 0) {
                            $this->storeLogEntry($log_entry);
                        }
                        elseif ($log_entry['deleted']) {
                            $this->destroyLogEntry($log_entry['id']);
                        }
                        else {
                            if ($field->type === 'notes') {
                                $log_entry['log_field1'] = $this->userNameToId($log_entry['log_field1']);
                            }
                            $this->updateLogEntry($log_entry);
                        }
                    }
                    $inputs[$field->column_name] = null;
                }
            }
        }

        // Update shop inputs
        \App\Shop::where('id', $id)
            ->update($inputs);

        $shop = \App\Shop::find($id);

        // Group log entries by field id.
        $log_entries = $shop->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        $categories = \App\Category::where('source_class', 'Shop')->get();
        foreach ($categories as $category) {
            foreach($category->fields as $field) {
                if (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to shop object.
                    if (!empty($log_entries->get($field->id))) {
                        $field_log_entries = $log_entries->get($field->id)->all();

                        if ($field->type === 'notes') {
                            foreach ($field_log_entries as $index => $note) {
                                $field_log_entries[$index]['log_field1'] = $this->userIdToName($note['log_field1']);
                            }
                        }

                        $shop[$field->column_name] = $field_log_entries;
                    }
                }
            }
        }

        unset($shop->log_entries);

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
        $shop = \App\Shop::find($id);

        $shop->delete();

        $response = [
            'message' => "Shop: {$shop->shop_name}, has been deleted.",
            'data'    => $shop
        ];

        return response()->json($response, 200);
    }

    private function userIdToName($id)
    {
        $users = $this->users->keyBy('id');

        return $users[$id]->name;
    }

    private function userNameToId($name)
    {
        $users = $this->users->keyBy('name');

        return $users[$name]->id;
    }

    private function storeLogEntry($log_entry)
    {
        unset($log_entry['id'], $log_entry['deleted']);
        \App\LogEntry::create($log_entry);
    }

    private function updateLogEntry($log_entry)
    {
        unset($log_entry['deleted']);
        \App\LogEntry::where('id', $log_entry['id'])
            ->update($log_entry);
    }

    private function destroyLogEntry($id) {
        $log_entry = \App\LogEntry::find($id);
        $log_entry->delete();
    }

}
