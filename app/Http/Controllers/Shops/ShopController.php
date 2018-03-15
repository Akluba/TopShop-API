<?php

namespace App\Http\Controllers\Shops;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Shop;
use App\Category;
use App\Field;
use App\User;
use App\LogEntry;

class ShopController extends Controller
{
    private $users;

    public function __construct()
    {
        $this->users = User::all();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get All Shops.
        $shops = Shop::all();

        // Get Shop fields.
        $fields = Field::where('source_class', 'Shop')
                    ->whereNotIn('type', ['notes', 'log'])
                    ->get()->keyBy('column_name');

        // Get Select Options.
        $fields->map(function ($field) {
            if (in_array($field->type, ['select','select_multiple'])) {
                $options = $field->options()->get()->keyBy('id');
                $field->options = $options->toArray();
            }
        });

        // Format Shop Values.
        $shops->map(function ($shop) use ($fields) {
            foreach ($fields as $custom => $field) {
                if (!is_null($shop[$custom]) && in_array($field->type, ['select','select_multiple'])) {
                    if ($field->type === 'select') {
                        $shop[$custom] = $field['options'][$shop[$custom]]['title'];
                    } else {
                        $option_string_array = array();
                        foreach (json_decode($shop[$custom]) as $option) {
                            $option_string_array[] = $field['options'][$option]['title'];
                        }
                        $shop[$custom] = $option_string_array;
                    }
                } elseif ($field->type === 'checkbox') {
                    $shop[$custom] = $shop[$custom] ? 'true' : 'false';
                }
            }
        });

        $data = [
            'shop_list' => $shops,
            'fields' => $fields
        ];

        $response = [
            'message' => 'List of all Shops',
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
            'name' => 'required'
        ]);

        $name = $request->input('name');

        $shop = new Shop;
        $shop->name = $name;
        $shop->save();

        $response = [
            'message' => "Shop: {$shop->name}, has been created.",
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
        // Get Shop.
        $shop = Shop::find($id);

        // Get Shop's Log Entries.
        $log_entries = $shop->log_entries()->get()
                            ->sortByDesc('id')
                            ->groupBy('field_id');

        // Get Shop Categories.
        $categories = Category::where('source_class', 'Shop')->get();

        // Get Shop Form Elements.
        $form_elements = $categories->map(function ($category) {
            return $this->getFormElements($category);
        });

        // Populate Shop Object with Log Entries.
        foreach ($form_elements as $category) {
            foreach ($category->fields->whereIn('type', ['log','notes']) as $field) {
                if (!empty($log_entries->get($field->id))) {
                    $shop[$field->column_name] = $this->getFieldLogEntries($field, $log_entries);
                }
            }
        }

        $data = [
            'shop'          => $shop,
            'form_elements' => $form_elements
        ];

        $response = [
            'message' => "Displaying shop details for: {$shop->name}",
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
        // Get updated input data from request.
        $input = $request->except('id','_method');

        // Get Shop Categories.
        $categories = Category::where('source_class', 'Shop')->get();

        // Handle Log Entry Fields.
        foreach ($categories as $category) {
            foreach ($category->fields->whereIn('type', ['log','notes']) as $field) {
                if (array_key_exists($field->column_name, $input)) {
                    // Add / Update / Soft Delete Log Entries.
                    $this->handleLogEntries($field, $input[$field->column_name]);
                    // Set input to null.
                    $input[$field->column_name] = null;
                }
            }
        }

        // Update the Shop Object.
        Shop::where('id', $id)
            ->update($input);

        // Get the updated Shop object.
        $shop = Shop::find($id);

        // Get Shop's Log Entries.
        $log_entries = $shop->log_entries()->get()
                            ->sortByDesc('id')
                            ->groupBy('field_id');

        // Populate Shop Object with Log Entries.
        foreach ($categories as $category) {
            foreach ($category->fields->whereIn('type', ['log','notes']) as $field) {
                if (!empty($log_entries->get($field->id))) {
                    $shop[$field->column_name] = $this->getFieldLogEntries($field, $log_entries);
                }
            }
        }

        $response = [
            'message' => "{$shop->name} has been updated.",
            'shop'    => $shop
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
        $shop = \App\Shop::find($id);

        $shop->delete();

        $response = [
            'message' => "Shop: {$shop->name}, has been deleted.",
            'data'    => $shop
        ];

        return response()->json($response, 200);
    }

    private function getFormElements($category)
    {
        foreach ($category->fields as $field) {
            // Get Field Options
            if (in_array($field->type, ['select','select_multiple'])) {
                $field->options;
            }

            // Get Columns and Column Options.
            if (in_array($field->type, ['log','notes'])) {
                foreach ($field->columns as $column) {
                    if (in_array($column->type, ['select','select_multiple'])) {
                        $column->options;
                    } elseif (in_array($column->type, ['manager_link','shop_link'])) {
                        $options = ($column->type === 'manager_link') ? \App\Manager::all(['id','name']) : \App\Shop::all(['id','name']);
                        $column->options = $options->toArray();
                    }
                }
            }
        }

        return $category;
    }

    private function getFieldLogEntries($field, $log_entries)
    {
        $field_log_entries = $log_entries->get($field->id);

        if ($field->type === 'notes') {
            $field_log_entries->map(function ($note) {
                $note->log_field1 = $this->userIdToName($note->log_field1);
            });
        }

        return $field_log_entries->toArray();
    }

    private function handleLogEntries($field, $field_log_entries)
    {
        foreach ($field_log_entries as $log_entry) {
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
        LogEntry::create($log_entry);
    }

    private function updateLogEntry($log_entry)
    {
        unset($log_entry['deleted']);
        LogEntry::where('id', $log_entry['id'])
            ->update($log_entry);
    }

    private function destroyLogEntry($id)
    {
        $log_entry = LogEntry::find($id);
        $log_entry->delete();
    }
}
