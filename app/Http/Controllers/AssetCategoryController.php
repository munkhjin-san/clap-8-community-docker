<?php

namespace App\Http\Controllers;

use App\Models\AssetCategoryItem;
use App\Models\AssetCategoryItemField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetCategoryController extends Controller
{
    private function activeUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        $sub = $user->linked()->where('main_id', $user->id)->wherePivot('active', 1)->first();
        return $sub ?: $user;
    }

    private function ensureAdmin(): void
    {
        $activeUserId = $this->activeUser()->id;
        if (!in_array($activeUserId, [608, 610], true)) {
            abort(403, 'Forbidden');
        }
    }

    public function get_asset_category_items()
    {
        $items = AssetCategoryItem::query()
            ->with(['fields'])
            ->orderByRaw('sort_order IS NULL, sort_order')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json($items);
    }

    // Backward-compatible alias (the UI used to call this)
    public function get_asset_categories()
    {
        return $this->get_asset_category_items();
    }

    public function create_asset_category_item(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'in:asset,account'],
            'title' => ['required', 'string', 'max:255'],
            'required_data' => ['nullable', 'string', 'max:255'],
        ]);

        $maxSort = AssetCategoryItem::max('sort_order');
        $nextSort = is_numeric($maxSort) ? ((int) $maxSort + 1) : 1;

        $item = AssetCategoryItem::create([
            'type' => $validated['type'] ?? 'asset',
            'title' => $validated['title'],
            'sort_order' => $nextSort,
        ]);

        return response()->json($item);
    }

    public function update_asset_category_item(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:asset_category_items,id'],
            'type' => ['nullable', 'string', 'in:asset,account'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $item = AssetCategoryItem::findOrFail($validated['id']);
        $update = [
            'title' => $validated['title'],
        ];
        if (array_key_exists('type', $validated) && $validated['type'] !== null) {
            $update['type'] = $validated['type'];
        }
        $item->update($update);

        return response()->json($item);
    }

    public function delete_asset_category_item(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:asset_category_items,id'],
        ]);

        $item = AssetCategoryItem::findOrFail($validated['id']);
        $item->delete();

        return response()->json(['message' => 'Successfully deleted']);
    }

    public function create_asset_category_item_field(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'asset_category_item_id' => ['required', 'integer', 'exists:asset_category_items,id'],
            'key' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'input_type' => ['required', 'string', 'in:shorttext,longtext,password'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string', 'max:255'],
            'visible' => ['nullable', 'string', 'in:public,private,user'],
            'editable' => ['nullable', 'boolean'],
        ]);

        $maxSort = AssetCategoryItemField::query()
            ->where('asset_category_item_id', $validated['asset_category_item_id'])
            ->max('sort_order');
        $nextSort = is_numeric($maxSort) ? ((int) $maxSort + 1) : 1;

        $field = AssetCategoryItemField::create([
            ...$validated,
            'visible' => $validated['visible'] ?? 'public',
            'editable' => $validated['editable'] ?? true,
            'sort_order' => $nextSort,
        ]);
        return response()->json($field);
    }

    public function reorder_asset_category_items(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:asset_category_items,id'],
        ]);

        $ids = $validated['ids'];

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                AssetCategoryItem::where('id', $id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return response()->json(['message' => 'ok']);
    }

    public function reorder_asset_category_item_fields(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'asset_category_item_id' => ['required', 'integer', 'exists:asset_category_items,id'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:asset_category_item_fields,id'],
        ]);

        $itemId = (int) $validated['asset_category_item_id'];
        $ids = $validated['ids'];

        // Ensure all fields belong to the item (avoid cross-item reorder).
        $count = AssetCategoryItemField::query()
            ->where('asset_category_item_id', $itemId)
            ->whereIn('id', $ids)
            ->count();
        if ($count !== count($ids)) {
            abort(422, 'Invalid field ids');
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                AssetCategoryItemField::where('id', $id)->update([
                    'sort_order' => $index + 1,
                ]);
            }
        });

        return response()->json(['message' => 'ok']);
    }

    public function update_asset_category_item_field(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:asset_category_item_fields,id'],
            'asset_category_item_id' => ['required', 'integer', 'exists:asset_category_items,id'],
            'key' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'input_type' => ['required', 'string', 'in:shorttext,longtext,password'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string', 'max:255'],
            'visible' => ['nullable', 'string', 'in:public,private,user'],
            'editable' => ['nullable', 'boolean'],
        ]);

        $field = AssetCategoryItemField::findOrFail($validated['id']);
        $field->update([
            'asset_category_item_id' => $validated['asset_category_item_id'],
            'key' => $validated['key'] ?? null,
            'label' => $validated['label'],
            'input_type' => $validated['input_type'],
            'placeholder' => $validated['placeholder'],
            'rules' => $validated['rules'],
            'visible' => $validated['visible'] ?? 'public',
            'editable' => $validated['editable'] ?? true,
        ]);

        return response()->json($field);
    }

    public function delete_asset_category_item_field(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:asset_category_item_fields,id'],
        ]);

        $field = AssetCategoryItemField::findOrFail($validated['id']);
        $field->delete();

        return response()->json(['message' => 'Successfully deleted']);
    }

    public function duplicate_asset_category(Request $request)
    {
        abort(404);
    }

    public function duplicate_asset_category_item(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:asset_category_items,id'],
        ]);

        $original = AssetCategoryItem::with(['fields'])->findOrFail($validated['id']);

        $duplicated = DB::transaction(function () use ($original) {
            $maxSort = AssetCategoryItem::max('sort_order');
            $nextSort = is_numeric($maxSort) ? ((int) $maxSort + 1) : null;

            $item = AssetCategoryItem::create([
                'type' => $original->type ?: 'asset',
                'title' => $original->title . '（コピー）',
                'sort_order' => $nextSort,
            ]);

            foreach ($original->fields as $field) {
                AssetCategoryItemField::create([
                    'asset_category_item_id' => $item->id,
                    'key' => null,
                    'label' => $field->label,
                    'input_type' => $field->input_type,
                    'placeholder' => $field->placeholder,
                    'rules' => $field->rules,
                    'visible' => $field->visible,
                    'editable' => $field->editable,
                    'sort_order' => $field->sort_order,
                ]);
            }

            return $item;
        });

        return response()->json($duplicated->load(['fields']));
    }
}
