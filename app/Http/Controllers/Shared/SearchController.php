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
            // Filter on field_id.
            $query->where('field_id', $field_id);

            // Filter out null filters.
            $set_filters = array_filter($field_filters, function($filter_array) {
                return array_filter($filter_array, function($filter) {
                    return $filter;
                });
            });

            if ($set_filters) {
                foreach ($set_filters as $filter_group) {
                    $this->setFilters($query, $filter_group);
                }
            }
        }

        // Get all the log entry records that match the filters.
        $matches = $query->get();

		// Create array of ids that matched filters.
		$sources = $matches
			->pluck('source_id')
			->unique();

		// Get all records
		$records = $this->getSourceRecords($source_class);

		// Filter records to those that match the filter criteria.
		$filtered_records = $records
			->whereIn('id', $sources)
            ->flatten(1);

        $field_array = \App\Category::elements($source_class);

        $filtered_records->map(function ($record) use ($field_array){
            foreach ($field_array as $custom => $field) {
                if (!is_null($record[$custom]) && in_array($field['type'], array('select','select_multiple'))) {
                    if ($field['type'] === 'select_multiple') {
                        $option_array = array();
                        foreach (json_decode($record[$custom]) as $option) {
                            $option_array[] = $field['options'][$option]['title'];
                        }
                        $record[$custom] = $option_array;
                    } else {
                        $record[$custom] = $field['options'][$record[$custom]]['title'];
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $record[$custom] = $record[$custom] ? 'true' : 'false';
                }
            }

            return $record;
        });

    	$response = [
            'matches' => $filtered_records,
            'fields' => $field_array
    	];

    	return response()->json($response, 200);
    }

    private function setFilters($query, $filters)
    {
    	foreach ($filters as $col => $value) {
			if ($value)
                $query->where($col, $value);
		}

    	return $query;
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
    	}
    }

}