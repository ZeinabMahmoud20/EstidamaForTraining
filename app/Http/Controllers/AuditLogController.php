<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $logs = AuditLog::query()
            ->with('user')
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('action', 'like', $like)
                    ->orWhere('entity_type', 'like', $like));
            })
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('entity_type'), fn ($q) => $q->where('entity_type', $request->input('entity_type')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'users' => User::orderBy('full_name')->pluck('full_name', 'id'),
            'entityTypes' => AuditLog::query()
                ->select('entity_type')
                ->whereNotNull('entity_type')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type'),
            'entityLabels' => config('audit.entities', []),
        ]);
    }
}
