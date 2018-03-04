<?php

namespace App\Http\Controllers\Managers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Manager;
use App\Category;
use App\LogEntry;

class ManagerController extends Controller
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
        $managers = \App\Manager::all();

        // Get Manager categories / fields / field options / field columns / column options.
        $categories = \App\Category::where('source_class', 'Manager')
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

        $managers->map(function ($manager) use ($field_array){
            foreach ($field_array as $custom => $field) {
                if (!is_null($manager[$custom]) && in_array($field['type'], array('select','select_multiple'))) {
                    if ($field['type'] === 'select_multiple') {
                        $option_array = array();
                        foreach (json_decode($manager[$custom]) as $option) {
                            $option_array[] = $field['options'][$option]['title'];
                        }
                        $manager[$custom] = $option_array;
                    } else {
                        $manager[$custom] = $field['options'][$manager[$custom]]['title'];
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $manager[$custom] = $manager[$custom] ? 'true' : 'false';
                }
            }

            return $manager;
        });

        $data = [
            'manager_list' => $managers,
            'fields' => $field_array
        ];

        $response = [
            'message' => 'List of all Managers',
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
            'name'       => 'required'
        ]);

        $name = $request->input('name');

        $manager = new Manager;
        $manager->name = $name;
        $manager->save();

        $response = [
            'message' => "Manager: {$manager->name}, has been created.",
            'data'    => $manager
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
        $manager = \App\Manager::find($id);

        // Group log entries by field id.
        $log_entries = $manager->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        // Get Manager categories / fields / field options / field columns / column options.
        $categories = \App\Category::where('source_class', 'Manager')->get();

        foreach ($categories as $category) {
            $fields = $category->fields;
            foreach($fields as $field) {
                if (in_array($field->type, array('select','select_multiple'))) {
                    $field->options;
                }
                elseif (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to manager object.
                    if (!empty($log_entries->get($field->id))) {
                        $field_log_entries = $log_entries->get($field->id)->all();

                        if ($field->type === 'notes') {
                            foreach ($field_log_entries as $index => $note) {
                                $field_log_entries[$index]['log_field1'] = $this->userIdToName($note['log_field1']);
                            }
                        }

                        $manager[$field->column_name] = $field_log_entries;
                    }
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

        unset($manager->log_entries);

         $data = [
            'manager'       => $manager,
            'form_elements' => $categories
        ];

        $response = [
            'message' => "Displaying manager details for: {$manager->name}",
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
        $categories = \App\Category::where('source_class', 'Manager')->get();
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

        // Update manager inputs
        \App\Manager::where('id', $id)
            ->update($inputs);

        $manager = \App\Manager::find($id);

        // Group log entries by field id.
        $log_entries = $manager->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        $categories = \App\Category::where('source_class', 'Manager')->get();
        foreach ($categories as $category) {
            foreach($category->fields as $field) {
                if (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to manager object.
                    if (!empty($log_entries->get($field->id))) {
                        $field_log_entries = $log_entries->get($field->id)->all();

                        if ($field->type === 'notes') {
                            foreach ($field_log_entries as $index => $note) {
                                $field_log_entries[$index]['log_field1'] = $this->userIdToName($note['log_field1']);
                            }
                        }

                        $manager[$field->column_name] = $field_log_entries;
                    }
                }
            }
        }

        unset($manager->log_entries);

        $response = [
            'message' => "{$manager->name} has been updated.",
            'data'    => $manager
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
        $manager = \App\Manager::find($id);

        $manager->delete();

        $response = [
            'message' => "Manager: {$manager->name}, has been deleted.",
            'data'    => $manager
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
