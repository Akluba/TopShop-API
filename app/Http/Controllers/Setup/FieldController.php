<?php

namespace App\Http\Controllers\Setup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Field;

class FieldController extends Controller
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
            'source_class' => 'required',
            'category_id'  => 'required',
            'title'        => 'required',
            'type'         => 'required'
        ]);

        $source_class = $request->input('source_class');
        $category_id  = $request->input('category_id');
        $title        = $request->input('title');
        $type         = $request->input('type');

        $field = new Field;

        $field->source_class = $source_class;
        $field->category_id  = $category_id;
        $field->title        = $title;
        $field->type         = $type;

        $field->save();

        $field_actions = [
            'href'   => '/api/field/'.$field->id,
            'method' => [
                'update'  => 'PUT',
                'destroy' => 'DELETE'
            ]
        ];

        if (in_array($field->type, array('log','select','select_multiple'))) {
            $field_actions['method']['show'] = 'GET';
        }

        $field->actions = $field_actions;

        $response = [
            'msg'   => 'Field created',
            'field' => $field
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
        $field = \App\Field::find($id);

        $category_id = $field->category->id;

        $actions = [
            'back' => [
                'href'   => '/api/category/'.$category_id,
                'method' => 'GET'
            ]
        ];

        if ($field->type == 'log') {
            $actions['store'] = [
                'href'   => '/api/column?field_id='.$field->id,
                'method' => 'POST'
            ];

            $columns = \App\Field::find($id)->columns;

            foreach($columns as $i => $column) {
                $column_actions = [
                    'href' => '/api/column/'.$column->id,
                    'method' => [
                        'update'  => 'PUT',
                        'destroy' => 'DELETE'
                    ]
                ];

                if (in_array($column->type, array('select','select_multiple'))) {
                    $column_actions['method']['show'] = 'GET';
                }

                $columns[$i]['actions'] = $column_actions;
            }

            $field->columns = $columns;
        }
        else {
            $actions['store'] = [
                'href'   => '/api/option?source_class=CustomField&source_id='.$field->id,
                'method' => 'POST'
            ];

            $options = \App\Field::find($id)->options()->where('source_class', 'CustomField')->get();

            foreach($options as $i => $option) {
                $options[$i]['actions'] = [
                    'href'   => '/api/option/'.$option->id,
                    'method' => [
                        'update'  => 'PUT',
                        'destroy' => 'DELETE'
                    ]
                ];
            }

            $field->options = $options;
        }

        $response = [
            'msg'     => 'Display specific Field',
            'actions' => $actions,
            'field'   => $field
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

        $field = \App\Field::find($id);

        $field->title = $title;
        $field->save();

        $response = [
            'msg'   => 'Updated Field',
            'field' => $field
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
        $field = \App\Field::find($id);

        $field->delete();

        $response = [
            'msg'   => 'Deleted Field',
            'field' => $field
        ];

        return response()->json($response, 201);
    }
}
