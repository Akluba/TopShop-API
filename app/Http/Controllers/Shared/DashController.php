<?php

namespace App\Http\Controllers\Shared;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Shared\NoteCollection;

use App\Field;
use App\Column;
use App\LogEntry;

class Filter {
    public $field_id;
    public $title;

    function __construct($field_id, $title) {
        $this->field_id = $field_id;
        $this->title = $title;
    }
}

class Note {
    public $created_by;
    public $created_date;
    public $created_for;
    public $note_text;
    public $tags;

    function __construct($created_by, $created_date, $created_for, $note_text, $tags = null)
    {
        $this->created_by = $created_by;
        $this->created_date = $created_date;
        $this->created_for = $created_for;
        $this->note_text = $note_text;
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

    /**
     * [index description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request)
    {
        // Get all note Fields.
        $note_fields = Field::where('type', 'notes')
                            ->get();

        // Quick hack to limit CPR users to only see CPR Notes.
        if ($request->user()->profile === 'cpr') {
            $note_fields = $note_fields->where('source_class', 'Cpr');
        }

        // Format available source class filter and field sub filters.
        $filters = $note_fields->mapToGroups(function ($field, $key) {
            return [$field->source_class => new Filter($field->id, $field->title)];
        })->toArray();

        $response = [
            'filters' => $filters
        ];

        return response()->json($response, 200);
    }

    /**
     * [notes description]
     * @param  [type]  $source_class [description]
     * @param  [type]  $field_id     [description]
     * @param  Request $request      [description]
     * @return [type]                [description]
     */
    public function notes($source_class = null, $field_id = null, Request $request)
    {
        // Get all note Fields.
        $note_fields = Field::where('type', 'notes')
                            ->get();

        // Quick hack to limit CPR users to only see CPR Notes.
        if ($request->user()->profile === 'cpr') {
            $note_fields = $note_fields->where('source_class', 'Cpr');
        }

        // Filter on either Source Class or Field ID.
        if (isset($source_class) || isset($field_id)) {
            $note_fields = (isset($field_id)) ? $note_fields->where('id', $field_id) : $note_fields->where('source_class', ucfirst($source_class));
        }

        // Get notes.
        $notes = $note_fields->map(function ($field) {
            return $this->getFieldNotes($field);
        })->flatten(1);

        // Sorting Notes by date created.
        $sorted_notes = $notes->sortByDesc(function ($note) {
            return strtotime($note->created_date);
        })->values()->all();

        // Paginate Notes.
        $paginated_notes = ( new NoteCollection($sorted_notes) )->paginate(15);

        return response()->json($paginated_notes, 200);
    }

    /**
     * [getFieldNotes description]
     * @param  [type] $field [description]
     * @return [type]        [description]
     */
    private function getFieldNotes($field)
    {
        // First create an array of active source_ids for the given source_class.
        $active = $this->sources[$field->source_class]
                        ->pluck('id')
                        ->toArray();

        // Get Notes for field.
        $field_notes = LogEntry::where('field_id', $field->id)
                            ->whereIn('source_id', $active)
                            ->get();

        // Format Notes.
        return $this->formatNotes($field->id, $field_notes);
    }

    /**
     * [formatNotes description]
     * @param  [type] $field_id    [description]
     * @param  [type] $field_notes [description]
     * @return [type]              [description]
     */
    private function formatNotes($field_id, $field_notes)
    {
        // Get Columns for Field.
        $tag_columns = Column::where('field_id', $field_id)
                            ->whereNull('system')
                            ->get(['column_name','type']);

        // Format Notes.
        $formatted_notes = $field_notes->map(function ($note) use ($tag_columns) {
            return new Note(
                $this->convertIdToText($note['log_field1']),
                $note['log_field2'],
                array(
                    'id' => $note['source_id'],
                    'class' => $note['source_class'],
                    'text' => $this->convertIdToText($note['source_id'], $note['source_class'])
                ),
                $note['log_field3'],
                $this->formatTagColumns($tag_columns, $note)
            );
        });

        return $formatted_notes;
    }

    /**
     * [formatTagColumns description]
     * @param  [type] $tag_columns [description]
     * @param  [type] $note        [description]
     * @return [type]              [description]
     */
    private function formatTagColumns($tag_columns, $note)
    {
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
        return !empty($note_tags) ? array_values($note_tags) : null;
    }

    /**
     * [extractTagData description]
     * @param  [type] $note   [description]
     * @param  [type] $column [description]
     * @return [type]         [description]
     */
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

    /**
     * [convertIdToText description]
     * @param  [type] $id           [description]
     * @param  [type] $source_class [description]
     * @return [type]               [description]
     */
    private function convertIdToText($id, $source_class = null)
    {
        if (is_null($source_class)) {
            return $this->users[$id]->name;
        }

        $source = $this->sources[$source_class];

        return $source[$id]['name'];
    }
}
