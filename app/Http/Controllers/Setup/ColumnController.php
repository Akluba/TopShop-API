<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Column;

class ColumnController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'field_id' => 'required',
            'type'     => 'required',
            'title'    => 'required'
        ]);

        $field_id = $request->input('field_id');
        $type     = $request->input('type');
        $title    = $request->input('title');
        $column_name  = \App\Column::nextLogColumnName($field_id);

        $column = New Column;

        $column->field_id    = $field_id;
        $column->column_name = $column_name;
        $column->type        = $type;
        $column->title       = $title;

        $column->save();

        $column->actions = [
            'href'   => '/api/column/'.$column->id,
            'method' => [
                'update'  => 'PUT',
                'destroy' => 'DELETE'
            ]
        ];

        $column_actions = [
            'href'   => '/api/column/'.$column->id,
            'method' => [
                'update'  => 'PUT',
                'destroy' => 'DELETE'
            ]
        ];

        if (in_array($column->type, array('select','select_multiple'))) {
            $column_actions['method']['show'] = 'GET';
        }

        $column->actions = $column_actions;

        $response = [
            'msg'    => 'Column created',
            'column' => $column
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
        $column = \App\Column::find($id);

        $field_id = $column->field->id;

        $actions = [
            'back'  => [
                'href'   => '/api/field/'.$field_id,
                'method' => 'GET'
            ],
            'store' => [
                'href'   => '/api/option?source_class=CustomFieldLogColumn&source_id='.$column->id,
                'method' => 'POST'
            ]
        ];

        $options = \App\Column::find($id)->options()->where('source_class', 'CustomFieldLogColumn')->get();

        foreach($options as $i => $option) {
            $options[$i]['actions'] = [
                'href'   => '/api/option/'.$option->id,
                'method' => [
                    'update'  => 'PUT',
                    'destroy' => 'DELETE'
                ]
            ];
        }

        $column->options = $options;

        $response = [
            'msg'     => 'Display specific Column',
            'actions' => $actions,
            'column'   => $column
        ];

        return response()->json($response, 201);
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
        $request->validate([
            'title'  => 'required',
        ]);

        $title = $request->input('title');

        $column = \App\Column::find($id);

        $column->title = $title;
        $column->save();

        $response = [
            'msg'    => 'Updated Column',
            'column' => $column
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
        $column = \App\Column::find($id);

        $column->delete();

        $response = [
            'msg'    => 'Deleted Column',
            'column' => $column
        ];

        return response()->json($response, 201);
    }
}
