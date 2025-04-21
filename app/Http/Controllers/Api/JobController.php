<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackgroundJobLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = BackgroundJobLog::orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query->paginate(15) ?: ['data' => [], 'current_page' => 1, 'last_page' => 1];
    }

    public function logs()
    {
        try {
            $logs = file(storage_path('logs/background_jobs.log'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            return response()->json(['logs' => array_slice($logs, -10)]); // Return last 10 logs
        } catch (\Exception $e) {
            Log::error('Failed to read logs', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Unable to read logs'], 500);
        }
    }

    public function cancel(Request $request, BackgroundJobLog $job)
    {
        if ($job->status !== 'running') {
            return response()->json(['error' => 'Job is not running'], 400);
        }

        try {
            $job->update([
                'status' => 'failed',
                'error_message' => 'Cancelled by user at ' . now()->toDateTimeString(),
            ]);
            return response()->json(['message' => 'Job cancelled successfully']);
        } catch (\Exception $e) {
            Log::error('Job cancellation failed', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to cancel job'], 500);
        }
    }
}
