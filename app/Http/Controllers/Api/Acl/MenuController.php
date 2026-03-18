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
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        // Return tree structure
        $menus = \App\Models\Menu::whereNull('parent_id')
            ->with(['children.roles', 'children.permissions', 'children.children', 'roles', 'permissions'])
            ->orderBy('order')
            ->get();

        if ($user->isSuperAdmin()) {
            return $menus;
        }

        // Recursive filter for permissions
        $filtered = $menus->map(function($menu) use ($user) {
            return $this->filterMenu($menu, $user);
        })->filter();

        return array_values($filtered->toArray());
    }

    protected function filterMenu($menu, $user)
    {
        // Check children first
            $filteredChildren = $menu->children->map(function($child) use ($user) {
                return $this->filterMenu($child, $user);
            })->filter()->values();
            
            $menu->setRelation('children', $filteredChildren);

        // If specific permissions are set, check them
        if ($menu->permissions->count() > 0) {
            $hasPermission = $menu->permissions->some(function($p) use ($user) {
                if ($user->hasPermission($p->name)) return true;

                // Dynamic access for Society leaders
                if (str_starts_with($p->name, 'societies.') && str_ends_with($p->name, '.view')) {
                    $abbr = strtoupper(explode('.', $p->name)[1]);
                    $society = \App\Models\Society::where('abbreviation', $abbr)->first();
                    if ($society && $user->can('view', $society)) {
                        return true;
                    }
                }
                
                return false;
            });
            
            // If parent has permissions but they fail, still show it if it has visible children
            if (!$hasPermission && $menu->children->count() === 0) {
                return null;
            }
        }

        // If no permissions set but has children, only show if at least one child is visible
        // (This is now partially redundant with the logic above but kept for clarity)
        if ($menu->children->count() > 0) {
            // Children were already filtered at the start of filterMenu
            if ($menu->children->count() === 0) return null;
        }

        // If no permissions and no children, it's a "public/default" menu item (e.g. Dashboard)
        
        return $menu;
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
