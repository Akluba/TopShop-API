<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Company;
use App\Category;
use App\Field;
use App\User;
use App\LogEntry;

class CompanyDetailController extends Controller
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
        // Get All Companies.
        $companies = Company::all();

        // Get Company fields.
        $fields = Field::where('source_class', 'Company')
                    ->whereNotIn('type', ['notes', 'log'])
                    ->get()->keyBy('column_name');

        // Get Select Options.
        $fields->map(function ($field) {
            if (in_array($field->type, ['select','select_multiple'])) {
                $options = $field->options()->get()->keyBy('id');
                $field->options = $options->toArray();
            }
        });

        // Format Company Values.
        $companies->map(function ($company) use ($fields) {
            foreach ($fields as $custom => $field) {
                if (!is_null($company[$custom]) && in_array($field->type, ['select','select_multiple'])) {
                    if ($field->type === 'select') {
                        $company[$custom] = $field['options'][$company[$custom]]['title'];
                    } else {
                        $option_string_array = array();
                        foreach (json_decode($company[$custom]) as $option) {
                            $option_string_array[] = $field['options'][$option]['title'];
                        }
                        $company[$custom] = $option_string_array;
                    }
                } elseif ($field->type === 'checkbox') {
                    $company[$custom] = $company[$custom] ? 'true' : 'false';
                }
            }
        });

        $data = [
            'company_list' => $companies,
            'fields' => $fields
        ];

        $response = [
            'message' => 'List of all Companies',
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

        $company = new Company;
        $company->name = $name;
        $company->save();

        $response = [
            'message' => "Company: {$company->name}, has been created.",
            'data'    => $company
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
        // Get Company.
        $company = Company::find($id);

        // Get Company's Log Entries.
        $log_entries = $company->log_entries()->get()
                            ->sortByDesc('id')
                            ->groupBy('field_id');

        // Get Company Categories.
        $categories = Category::where('source_class', 'Company')->get();

        // Get Company Form Elements.
        $form_elements = $categories->map(function ($category) {
            return $this->getFormElements($category);
        });

        // Populate Company Object with Log Entries.
        foreach ($form_elements as $category) {
            foreach ($category->fields->whereIn('type', ['log','notes']) as $field) {
                if (!empty($log_entries->get($field->id))) {
                    $company[$field->column_name] = $this->getFieldLogEntries($field, $log_entries);
                }
            }
        }

        $data = [
            'company'       => $company,
            'form_elements' => $form_elements
        ];

        $response = [
            'message' => "Displaying company details for: {$company->name}",
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

        // Get Company Categories.
        $categories = Category::where('source_class', 'Company')->get();

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

        // Update the Company Object.
        Company::where('id', $id)
            ->update($input);

        // Get the updated Company object.
        $company = Company::find($id);

        // Get Company's Log Entries.
        $log_entries = $company->log_entries()->get()
                            ->sortByDesc('id')
                            ->groupBy('field_id');

        // Populate Company Object with Log Entries.
        foreach ($categories as $category) {
            foreach ($category->fields->whereIn('type', ['log','notes']) as $field) {
                if (!empty($log_entries->get($field->id))) {
                    $company[$field->column_name] = $this->getFieldLogEntries($field, $log_entries);
                }
            }
        }

        $response = [
            'message' => "{$company->name} has been updated.",
            'company' => $company
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
        $company = Company::find($id);

        $company->delete();

        $response = [
            'message' => "Company: {$company->name}, has been deleted.",
            'data'    => $company
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
