<?php

namespace App\Http\Controllers\Api\Acl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Return tree structure
        return \App\Models\Menu::whereNull('parent_id')
            ->with(['children.roles', 'children.permissions', 'children.children', 'roles', 'permissions'])
            ->orderBy('order')
            ->get();
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'url' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => 'nullable|exists:menus,id',
            'nodes' => 'array', // For roles/permissions assignment
        ]);

        $menu = \App\Models\Menu::create($validated);
        
        if ($request->has('roles')) $menu->roles()->sync($request->input('roles'));
        if ($request->has('permissions')) $menu->permissions()->sync($request->input('permissions'));

        return response()->json($menu, 201);
    }

    public function update(\Illuminate\Http\Request $request, $id)
    {
        $menu = \App\Models\Menu::findOrFail($id);
        $menu->update($request->all());

        if ($request->has('roles')) $menu->roles()->sync($request->input('roles'));
        if ($request->has('permissions')) $menu->permissions()->sync($request->input('permissions'));

        return response()->json($menu);
    }

    public function destroy($id)
    {
        \App\Models\Menu::findOrFail($id)->delete();
        return response()->noContent();
    }

    public function reorder(Request $request) {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menus,id',
            'items.*.order' => 'required|integer',
        ]);

        foreach ($data['items'] as $item) {
            \App\Models\Menu::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Order updated']);
    }
}
