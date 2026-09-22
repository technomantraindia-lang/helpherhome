<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Worker;
use App\Services\DocumentCenterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentCenterController extends Controller
{
    public function index(Request $request, DocumentCenterService $service): View
    {
        return view('admin.documents.index', ['documents' => $service->search($request->all()), 'customers' => Customer::orderBy('name')->get(['id', 'name']), 'workers' => Worker::orderBy('name')->get(['id', 'name'])]);
    }
}
