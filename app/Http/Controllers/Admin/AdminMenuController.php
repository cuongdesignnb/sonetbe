<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class AdminMenuController extends Controller
{
    /**
     * List all menus as nested tree (for admin)
     */
    public function index()
    {
        return response()->json([
            'menus' => Menu::tree(false),
        ]);
    }

    /**
     * Create a new menu item
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => 'nullable|integer',
            'title'     => 'required|string|max:255',
            'url'       => 'required|string|max:500',
            'target'    => 'nullable|string|in:_self,_blank',
            'icon'      => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        // Auto-set position to end
        $maxPosition = Menu::where('parent_id', $data['parent_id'] ?? null)->max('position') ?? -1;
        $data['position'] = $maxPosition + 1;
        $data['target'] = $data['target'] ?? '_self';
        $data['is_active'] = $data['is_active'] ?? true;

        $menu = Menu::create($data);

        return response()->json([
            'menu' => $menu->load('children'),
            'message' => 'Menu item created successfully.',
        ], 201);
    }

    /**
     * Update a menu item
     */
    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);

        $data = $request->validate([
            'parent_id' => 'nullable|integer',
            'title'     => 'sometimes|required|string|max:255',
            'url'       => 'sometimes|required|string|max:500',
            'target'    => 'nullable|string|in:_self,_blank',
            'icon'      => 'nullable|string|max:100',
            'position'  => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Prevent self-referencing parent
        if (isset($data['parent_id']) && (int) $data['parent_id'] === (int) $id) {
            return response()->json(['message' => 'A menu item cannot be its own parent.'], 422);
        }

        $menu->update($data);

        return response()->json([
            'menu' => $menu->fresh()->load('children'),
            'message' => 'Menu item updated successfully.',
        ]);
    }

    /**
     * Delete a menu item (cascade deletes children)
     */
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return response()->json([
            'message' => 'Menu item deleted successfully.',
        ]);
    }

    /**
     * Reorder menus (drag & drop support)
     * Expects: { items: [ { id, parent_id, position }, ... ] }
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'items'            => 'required|array',
            'items.*.id'       => 'required|integer|exists:menus,id',
            'items.*.parent_id' => 'nullable|integer',
            'items.*.position' => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            Menu::where('id', $item['id'])->update([
                'parent_id' => $item['parent_id'] ?? null,
                'position'  => $item['position'],
            ]);
        }

        return response()->json([
            'menus' => Menu::tree(false),
            'message' => 'Menu order updated successfully.',
        ]);
    }
}
