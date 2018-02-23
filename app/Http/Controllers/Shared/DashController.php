<?php

namespace App\Http\Controllers\Shared;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Note {
    public $created_by;
    public $created_date;
    public $created_for;
    public $note_text;
    public $filters;
    public $tags;

    function __construct($created_by, $created_date, $created_for, $note_text, $filters, $tags = null)
    {
        $this->created_by = $created_by;
        $this->created_date = $created_date;
        $this->created_for = $created_for;
        $this->note_text = $note_text;
        $this->filters = $filters;
        $this->tags = $tags;
    }
}

class DashController extends Controller
{
    private $users;
    private $sources;

    function __construct()
    {
        $this->users = \App\User::all()->keyBy('id');
        $this->sources = [
            'Shop' => \App\Shop::all()->keyBy('id'),
            'Manager' => \App\Manager::all()->keyBy('id'),
            'Vendor' => \App\Vendor::all()->keyBy('id'),
            'Cpr' => \App\CPR::all()->keyBy('id')
        ];
    }

    public function index()
    {
        // Get all note fields.
        $note_fields = \App\Field::where('type', 'notes')
            ->get();

        // Collection to add formatted notes to.
        $notes = collect();

        foreach ($note_fields as $field) {

            // Get non system columns.
            $tag_columns = \App\Column::where('field_id', $field->id)
                ->whereNull('system')
                ->get(['column_name','type']);

            // Get note entries, exclude entries with deleted source_id records.
            $name_array = $this->sources[$field->source_class]
                ->pluck('id')
                ->toArray();

            $field_notes = \App\LogEntry::where('field_id', $field->id)
                ->whereIn('source_id', $name_array)
                ->get();

            // Insert formatted notes into notes collection.
            $notes[] = $field_notes->map(function ($note) use ($tag_columns) {
                $note_tags = null;
                // When tag columns exist, format them.
                if ($tag_columns) {
                    $note_tags = $tag_columns->map(function ($column) use ($note) {
                        if (!is_null($note[$column['column_name']])) {
                            return $this->extractTagData($note, $column);
                        }
                    })->toArray();
                }

                // Filter out Null values.
                $note_tags = array_filter($note_tags);

                // When note tags are empty, set to null
                $note_tags = !empty($note_tags) ? array_values($note_tags) : null;

                return new Note(
                    $this->convertIdToText($note['log_field1']),
                    $note['log_field2'],
                    array(
                        'id' => $note['source_id'],
                        'class' => $note['source_class'],
                        'text' => $this->convertIdToText($note['source_id'], $note['source_class'])
                    ),
                    $note['log_field3'],
                    array(
                        'class' => $note['source_class'],
                        'field' => $note['field_id']
                    ),
                    $note_tags
                );
            });
        }

        $filters = $note_fields->mapToGroups(function ($field, $key) {
            return [$field['source_class'] => [
                'field_id' => $field->id,
                'title' => $field->title
            ]];
        })->toArray();

        $notes = $notes->flatten(1)
            ->values()->all();

        $response = [
            'filters' => $filters,
            'notes' => $notes
        ];

        return response()->json($response, 200);
    }

    private function extractTagData($note, $column)
    {
        if (!in_array($column['type'], array('manager_link','shop_link'))) {
            return [
                'type' => $column['type'],
                'value' => $note[$column['column_name']]
            ];
        }

        $source = ($column['type'] === 'shop_link') ? $this->sources['Shop'] : $this->sources['Manager'];

        return [
            'type' => $column['type'],
            'id' => $note[$column['column_name']],
            'value' => $source[$note[$column['column_name']]]['name']
        ];
    }

    private function convertIdToText($id, $source_class = null)
    {
        if (is_null($source_class)) {
            return $this->users[$id]->name;
        }

        $source = $this->sources[$source_class];

        return $source[$id]['name'];
    }
}
