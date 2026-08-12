<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::with(['user', 'entity'])
            ->latest('created_at')
            ->paginate(30);

        return view('monitoring.audit-logs.index', ['logs' => $logs]);
    }
}
