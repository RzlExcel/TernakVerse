<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AuditLog;

class AdminAuditLogController extends Controller
{
    /**
     * Halaman Rekam Jejak Forensik Audit Trail (Inertia v2 + Vue 3).
     */
    public function index(Request $request): Response
    {
        $logs = AuditLog::with('user:id,nama,email,role')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs', [
            'logs' => $logs,
        ]);
    }
}
