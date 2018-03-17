<?php

namespace App\Http\Controllers\Shared;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as BaseCollection;

class NoteCollection extends BaseCollection {
	public function paginate( $perPage, $total = null, $page = null, $pageName = 'page' )
	{
		$page = $page ?: LengthAwarePaginator::resolveCurrentPage( $pageName );

		$current_items = array_slice($this->items, $perPage * ($page - 1), $perPage);

		return new LengthAwarePaginator( $current_items, $total ?: $this->count(), $perPage, $page, [
			'path' => LengthAwarePaginator::resolveCurrentPath(),
			'pageName' => $pageName,
		]);
	}
}