<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackgroundJobLog;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index()
    {
        return BackgroundJobLog::orderBy('created_at', 'desc')->paginate(10);
    }

    public function logs()
    {
        return response()->json([
            'logs' => file(storage_path('logs/background_jobs.log'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES),
        ]);
    }

    public function cancel(Request $request, BackgroundJobLog $job)
    {
        if ($job->status === 'running') {
            $job->update(['status' => 'failed', 'error_message' => 'Cancelled by user']);
            return response()->json(['message' => 'Job cancelled']);
        }
        return response()->json(['error' => 'Job not running'], 400);
    }
}
