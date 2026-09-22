<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAgencySettingsRequest;
use App\Models\AgencySetting;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class AgencySettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.agency', ['settings' => AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home'])]);
    }

    public function update(UpdateAgencySettingsRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $settings = AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home']);
        $data = $request->safe()->except(['logo', 'stamp', 'signature', 'upi_qr']);

        foreach ([
            'logo' => ['logo_path', 'logo'],
            'stamp' => ['stamp_path', 'stamp'],
            'signature' => ['signature_path', 'signature'],
            'upi_qr' => ['upi_qr_path', 'upi'],
        ] as $input => [$column, $directory]) {
            if ($request->hasFile($input)) {
                $oldPath = $settings->{$column};
                $disk = $input === 'logo' ? 'public' : 'local';
                $data[$column] = $request->file($input)->store('agency/'.$directory, $disk);
                if ($oldPath) { Storage::disk($disk)->delete($oldPath); Storage::disk($disk === 'local' ? 'public' : 'local')->delete($oldPath); }
            }
        }

        $settings->update($data);
        $logger->log('updated', 'settings', $settings, 'Agency settings updated.');

        return back()->with('success', 'Agency settings saved successfully.');
    }

    public function asset(Request $request, string $asset): StreamedResponse
    {
        abort_unless(in_array($asset, ['logo', 'stamp', 'signature', 'upi'], true), 404);
        $column = $asset === 'upi' ? 'upi_qr_path' : $asset.'_path';
        $settings = AgencySetting::firstOrFail();
        $path = $settings->{$column};
        $disk = $asset === 'logo' ? 'public' : 'local';
        if (!$path || !Storage::disk($disk)->exists($path)) {
            $disk = $disk === 'local' ? 'public' : 'local';
        }
        abort_unless($path && Storage::disk($disk)->exists($path), 404);
        return Storage::disk($disk)->response($path);
    }
}
