<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResolutionController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Resolution::with(['meeting', 'responsible']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('topic', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'topic' => 'required|string',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'status' => 'required|in:Pendente,Em Andamento,Cumprida,Recorrente',
            'responsible_id' => 'nullable|exists:members,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $resolution = \App\Models\Resolution::create($validated);
        return response()->json($resolution, 201);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Resolution $resolution)
    {
        $validated = $request->validate([
            'topic' => 'sometimes|string',
            'content' => 'sometimes|string',
            'tags' => 'nullable|array',
            'status' => 'sometimes|in:Pendente,Em Andamento,Cumprida,Recorrente',
            'responsible_id' => 'nullable|exists:members,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $resolution->update($validated);
        return response()->json($resolution);
    }

    public function destroy(\App\Models\Resolution $resolution)
    {
        $resolution->delete();
        return response()->noContent();
    }
}
