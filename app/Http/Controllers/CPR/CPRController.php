<?php

namespace App\Http\Controllers\CPR;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\CPR;

class CPRController extends Controller
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

        $cpr_list = CPR::all();

        $data = [
            'cpr_list' => $cpr_list
        ];

        $response = [
            'message' => 'List of all CRP',
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

        $cpr_name = $request->input('name');

        $cpr = new CPR;
        $cpr->name = $cpr_name;
        $cpr->save();

        $response = [
            'message' => "CPR: {$cpr->name}, has been created.",
            'data'    => $cpr
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
        $cpr = CPR::find($id);

        // Group log entries by field id.
        $log_entries = $cpr->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        // Get CPR categories / fields / field options / field columns / column options.
        $categories = \App\Category::where('source_class', 'Cpr')->get();

        foreach ($categories as $category) {
            foreach($category->fields as $field) {
                if (in_array($field->type, array('select','select_multiple'))) {
                    $field->options;
                }
                elseif (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to cpr object.
                    if (!empty($log_entries->get($field->id))) {
                        $field_log_entries = $log_entries->get($field->id)->all();

                        if ($field->type === 'notes') {
                            foreach ($field_log_entries as $index => $note) {
                                $field_log_entries[$index]['log_field1'] = $this->userIdToName($note['log_field1']);
                            }
                        }

                        $cpr[$field->column_name] = $field_log_entries;
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

        unset($cpr->log_entries);

         $data = [
            'cpr'            => $cpr,
            'form_elements'   => $categories
        ];

        $response = [
            'message' => "Displaying cpr details for: {$cpr->name}",
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
        unset($inputs['id'], $inputs['_method']);

        // Update log entries
        $categories = \App\Category::where('source_class', 'Cpr')->get();
        foreach ($categories as $category) {
            foreach($category->fields as $field) {
                if (in_array($field->type, array('log','notes')) && array_key_exists($field->column_name, $inputs)) {
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

        // Update cpr inputs
        CPR::where('id', $id)
            ->update($inputs);

        $cpr = CPR::find($id);

        // Group log entries by field id.
        $log_entries = $cpr->log_entries->sortByDesc('id')->mapToGroups(function($log_entry) {
            return [$log_entry['field_id'] => $log_entry];
        });

        $categories = \App\Category::where('source_class', 'Cpr')->get();
        foreach ($categories as $category) {
            foreach($category->fields as $field) {
                if (in_array($field->type, array('log','notes'))) {
                    // Adding log entry array to cpr object.
                    if (!empty($log_entries->get($field->id))) {
                        $field_log_entries = $log_entries->get($field->id)->all();

                        if ($field->type === 'notes') {
                            foreach ($field_log_entries as $index => $note) {
                                $field_log_entries[$index]['log_field1'] = $this->userIdToName($note['log_field1']);
                            }
                        }

                        $cpr[$field->column_name] = $field_log_entries;
                    }
                }
            }
        }

        unset($cpr->log_entries);

        $response = [
            'message' => "{$cpr->name} has been updated.",
            'cpr'    => $cpr
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
