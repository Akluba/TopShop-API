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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $managers = \App\Manager::all();

        $response = [
            'message' => 'List of all Active Managers',
            'data'    => $managers
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
            'manager_name'       => 'required'
        ]);

        $manager_name = $request->input('manager_name');

        $manager = new Manager;
        $manager->manager_name = $manager_name;
        $manager->save();

        $response = [
            'message' => "Shop: {$manager->manager_name}, has been created.",
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
        $field_log_entries = $manager->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        // Get Shop categories / fields / field options / field columns / column options.
        $categories = \App\Category::where('source_class', 'Manager')->get();
        foreach ($categories as $category) {
            $fields = $category->fields;
            foreach($fields as $field) {
                if (in_array($field->type, array('select','select_multiple'))) {
                    $field->options;
                }
                elseif (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to shop object.
                    if (!empty($field_log_entries->get($field->id))) {
                        $manager[$field->column_name] = $field_log_entries->get($field->id)->all();
                    }
                    foreach($field->columns as $column) {
                        if (in_array($column->type, array('select','select_multiple'))) {
                            $column->options;
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
            'message' => "Displaying manager details for: {$manager->manager_name}",
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
        foreach ($inputs as $custom => $input) {
            if (is_array($input)) {
                $inputs[$custom] = null;
                foreach ($input as $log_entry) {
                    if ($log_entry['id'] === 0) {
                        $this->storeLogEntry($log_entry);
                    }
                    elseif ($log_entry['deleted']) {
                        $this->destroyLogEntry($log_entry['id']);
                    }
                    else {
                        $this->updateLogEntry($log_entry);
                    }
                }
            }
        }

        // Update shop inputs
        \App\Manager::where('id', $id)
            ->update($inputs);

        $manager = \App\Manager::find($id);

        // Group log entries by field id.
        $field_log_entries = $manager->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        $field_log_entries->toArray();

        $categories = \App\Category::where('source_class', 'Manager')->get();
        foreach ($categories as $category) {
            foreach($category->fields as $field) {
                if (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to shop object.
                     if (!empty($field_log_entries->get($field->id))) {
                        $manager[$field->column_name] = $field_log_entries->get($field->id)->all();
                    }
                }
            }
        }

        unset($manager->log_entries);

        $response = [
            'message' => "{$manager->manager_name} has been updated.",
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
            'message' => "Manager: {$manager->manager_name}, has been deleted.",
            'data'    => $manager
        ];

        return response()->json($response, 200);
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
