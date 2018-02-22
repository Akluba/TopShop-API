<?php

namespace App\Http\Controllers\Shared;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Field;

class SearchController extends Controller
{
	public function index(Request $request)
    {
    	$request->validate([
            'source_class' => 'required'
        ]);

        $source_class = ucfirst($request->input('source_class'));

    	$fields = Field::where('source_class', $source_class)
    		->whereIn('type', ['log','notes'])
    		->get();

    	$response = [
    		'fields' => $fields->toArray()
    	];

    	return response()->json($response, 200);
    }

    public function results(Request $request)
    {

    	$request->validate([
            'source_class' => 'required',
            'filters' => 'required',
        ]);

        $source_class = $request->input('source_class');
    	$filters = $request->input('filters');

    	// Get all source_class log entries.
    	$source_log_entries = \App\LogEntry::where('source_class', $source_class)
    	 	->get();

    	// Group log entries by source_id.
    	$grouped_log_entries = $source_log_entries
    		->groupBy('source_id');

    	// Loop through grouped log entry arrays, check for matches.
    	$matches = $grouped_log_entries->map(function ($source) use ($filters) {
    		dd($source->toArray());
    	});

    	$query = \App\LogEntry::select();

    	// Build query where methods.
    	foreach ($filters as $field => $field_filters) {
    		$query->where('field_id', $field);
    		$query->where(function ($query) use ($field_filters) {
    			$i = 0;
				foreach ($field_filters as $filter_group) {
					// Wrap the first field filter group in where function.
					if ($i == 0)
						$query->where(function ($query) use ($filter_group) {
							$this->setFilters($query, $filter_group);
						});

					// Wrap additional field filters in orWhere function.
					else
						$query->orWhere(function ($query) use ($filter_group) {
							$this->setFilters($query, $filter_group);
						});

					$i++;
				}
			});
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
			->whereIn('id', $sources);

    	$response = [
    		'filters' => $filters,
    		'matches' => $matches,
    		'sources' => $sources,
    		'records' => $filtered_records
    	];

    	return response()->json($response, 200);
    }

    private function setFilters($query, $filters)
    {
    	foreach ($filters as $col => $value) {
			$query->where($col, $value);
		}

    	return $query;
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
    	}
    }

}