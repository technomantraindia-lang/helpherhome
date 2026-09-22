<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function overview(Request $request, ReportService $reports)
    {
        return view('admin.reports.overview', ['data' => $reports->overview($request->query()), 'filters' => $request->query()]);
    }

    public function report(Request $request, ReportService $reports)
    {
        $report = (string) $request->segment(3);
        abort_unless(in_array($report, ['workers','customers','requirements','assignments','replacements','agreements','invoices','payments','outstanding','documents'], true), 404);
        $data = $reports->report($report, $request->query(), min(max($request->integer('per_page', 25), 10), 100));
        if ($report === 'workers') $data['utilization'] = $reports->workerUtilization();
        return view('admin.reports.index', compact('report','data'));
    }

    public function export(Request $request, ReportService $reports, ActivityLogger $logger): StreamedResponse
    {
        $report = (string) $request->segment(3);
        abort_unless(in_array($report, ['workers','customers','requirements','assignments','invoices','payments','outstanding'], true), 404);
        $rows = $reports->rowsForExport($report, $request->query());
        $headers = $reports->columns($report);
        $filename = $report.'-report-'.now()->format('Y-m-d').'.csv';
        $logger->log('exported', 'reports', null, ucfirst($report).' report exported.', ['filters' => $request->query()]);
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) fputcsv($out, is_array($row) ? $row : (array) $row);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
