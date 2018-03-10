<?php

namespace App\Http\Controllers\Shared;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Field;

class SearchController extends Controller
{
    /**
     * [index description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
	public function index(Request $request)
    {
    	$request->validate([
            'source_class' => 'required'
        ]);

        $source_class = $request->input('source_class');

    	$fields = Field::where('source_class', $source_class)
    		->whereIn('type', ['log', 'notes'])
    		->get();

        $grouped = $fields->mapToGroups(function ($field, $key) use ($source_class) {
            $field_columns = $field->columns()->get();

            if ($field->type === 'log') {
                foreach ($field_columns as $column) {
                    if (in_array($column->type, ['select','select_multiple']))
                        $column->options;
                }
            } elseif ($field->type === 'notes') {
                $field_columns = $field_columns->where('system', null);

                foreach ($field_columns as $key => $column) {
                    if (in_array($column->type, ['manager_link','shop_link'])) {
                        $link_source_class = str_replace('_link', '', $column->type);
                        $field_columns[$key]['options'] = $this->getSourceRecords($link_source_class);
                    }
                }

                $created_for = (object) [
                    'title' => 'Created For',
                    'column_name' => 'source_id',
                    'type' => $this->noteType($source_class),
                    'options' => $this->getSourceRecords($source_class)
                ];

                if ($created_for->type === 'select') {
                    $created_for->options->map(function ($option) {
                        return $option->title = $option->name;
                    });
                }

                $field_columns[] = $created_for;
                $field_columns = $field_columns->flatten(1);
            }

            $field->columns = $field_columns;

            return [$field['type'] => $field];
        });

        $grouped = $grouped->toArray();

    	$response = [
    		'fields' => $grouped
    	];

    	return response()->json($response, 200);
    }

    /**
     * [results description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function results(Request $request)
    {
        $request->validate([
            'source_class' => 'required',
            'filters' => 'required'
        ]);

        $source_class = $request->input('source_class');
        $filters = $request->input('filters');

        $query = \App\LogEntry::select();

        foreach ($filters as $field_id => $field_filters) {
            // Set field_id filter.
            $query->where('field_id', $field_id);

            foreach ($field_filters as $filter_array) {
                // Remove null filters.
                $valid_filters = array_filter($filter_array);

                // Set log_field filters.
                if ($valid_filters) {
                    $this->setFilters($query, $valid_filters);
                }
            }
        }

        // Get all the log entry records that match the filters.
        $matches = $query->get();

        // Get field Elements for the field being searched.
        $columns = \App\Field::find($field_id)->columns;

        // Add Options array for necessary column types.
        $columns = $columns->map(function ($column) {
            if (in_array($column->type, ['select','select_multiple'])) {
                $options = $column->options->keyBy('id');
                $column->options = $options;
            }

            return $column;
        })->keyBy('column_name');

        // Format match records.
        $matches = $matches->map(function ($match) use ($columns, $source_class) {
            foreach ($columns as $log_field => $column) {
                $this->formatMatchLogField($match, $log_field, $column);
            }

            $match->name = $this->getSourceRecords($source_class)->keyBy('id')->find($match->source_id)->name;

            return $match;
        });

        $response = [
            'matches' => $matches,
            'columns' => $columns
        ];

        return response()->json($response, 200);
    }

    private function getSourceRecords($source_class)
    {
        switch ($source_class) {
            case 'shop':
                return \App\Shop::all();
                break;
            case 'manager':
                return \App\Manager::all();
                break;
            case 'vendor':
                return \App\Vendor::all();
                break;
            case 'cpr':
                return \App\CPR::all();
                break;
            case 'user':
                return \App\User::all();
                break;
        }
    }

    private function noteType($source_class)
    {
        if ($source_class === 'shop') {
            return 'shop_link';
        } elseif ($source_class === 'manager') {
            return 'manager_link';
        } else {
            return 'select';
        }
    }

    private function setFilters($query, $filters)
    {
        foreach ($filters as $col => $value) {
            $query->where($col, $value);
        }

        return $query;
    }

    private function formatMatchLogField($match, $log_field, $column)
    {
        if (!is_null($match[$log_field]) && in_array($column['type'], array('select','select_multiple'))) {
            if ($column['type'] === 'select_multiple') {
                $option_array = array();
                foreach (json_decode($match[$log_field]) as $option) {
                    $option_array[] = $column['options'][$option]['title'];
                }
                $match[$log_field] = $option_array;
            } else {
                $match[$log_field] = $column['options'][$match[$log_field]]['title'];
            }
        } elseif ($column['type'] === 'checkbox') {
            $match[$log_field] = $match[$log_field] ? 'true' : 'false';
        } elseif ($column['type'] === 'user_stamp') {
            $match[$log_field] = $this->getSourceRecords('user')->keyBy('id')->find($match[$log_field])->name;
        } elseif (!is_null($match[$log_field]) && in_array($column['type'], ['shop_link', 'manager_link'])) {
            $link_source_class = str_replace('_link', '', $column->type);
            $match[$log_field] = $this->getSourceRecords($link_source_class)->keyBy('id')->find($match[$log_field])->name;
        }

        return $match;
    }
}