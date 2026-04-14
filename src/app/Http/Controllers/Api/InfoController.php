<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InfoController extends Controller
{
    /**
     * Display information about the current server instance.
     * The server name is dynamically derived from the container hostname.
     */
    public function index(Request $request)
    {
        $hostname = gethostname();
        $serverName = $this->formatHostname($hostname);
        $containerIp = gethostbyname($hostname);
        $serverPort = $_SERVER['SERVER_PORT'] ?? '9000';

        // Log the request
        RequestLog::create([
            'container_id' => $hostname,
            'endpoint' => '/api/server-info',
            'method' => $request->method(),
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Greetings from {$serverName} - Ready to serve!",
            'server' => $serverName,
            'container' => [
                'id' => $hostname,
                'ip' => $containerIp,
                'port' => $serverPort,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Health check endpoint.
     */
    public function health()
    {
        return response()->json([
            'status' => 'healthy',
            'container' => gethostname(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Store a request log with custom payload.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:255',
        ]);

        $hostname = gethostname();
        $log = RequestLog::create([
            'container_id' => $hostname,
            'endpoint' => '/api/requests',
            'method' => $request->method(),
            'client_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $validated,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data logged successfully',
            'server' => $this->formatHostname($hostname),
            'data' => $log,
        ], 201);
    }

    /**
     * List recent request logs.
     */
    public function list()
    {
        $logs = RequestLog::orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'server' => $this->formatHostname(gethostname()),
            'total' => $logs->count(),
            'data' => $logs,
        ]);
    }

    /**
     * Formats hostname (e.g., 'backend-01') to a display name (e.g., 'Backend-01').
     */
    private function formatHostname($hostname)
    {
        // If it looks like 'backend-XX', format it nicely.
        if (preg_match('/backend-(\d+)/i', $hostname, $matches)) {
            return 'Backend-' . $matches[1];
        }

        // Fallback to title case or the raw hostname
        return Str::title(str_replace('-', ' ', $hostname));
    }
}
