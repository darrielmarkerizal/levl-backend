<?php

namespace App\Support\Traits;

use Illuminate\Http\Request;

trait BuildsQueryBuilderRequest
{
    protected function buildQueryBuilderRequest(array $filters = []): Request
    {
        return new Request([
            'filter' => $filters,
            'include' => request()->query('include'),
            'sort' => request()->query('sort'),
        ]);
    }
}
