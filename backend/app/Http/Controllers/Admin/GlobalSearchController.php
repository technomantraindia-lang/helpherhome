<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search)
    {
        $term = trim((string) $request->query('q', ''));
        return view('admin.search.index', ['term' => $term, 'results' => $term === '' ? [] : $search->search($term)]);
    }
}
