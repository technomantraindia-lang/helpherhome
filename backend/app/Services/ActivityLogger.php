<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function log(
        string $action,
        ?string $module = null,
        ?Model $subject = null,
        ?string $description = null,
        array $metadata = [],
        ?Request $request = null,
    ): ActivityLog {
        $request ??= request();

        return ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'metadata' => $metadata ?: null,
        ]);
    }
}
