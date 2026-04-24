<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminMediaController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // FOLDERS
    // ─────────────────────────────────────────────────────────────

    public function folders(Request $request)
    {
        $this->ensureAdmin();

        // Return flat list with parent_id for client-side tree building
        $folders = MediaFolder::orderBy('order')->orderBy('name')->get([
            'id', 'name', 'slug', 'parent_id', 'order', 'created_at'
        ]);

        // Also return counts per folder
        $counts = MediaAsset::selectRaw('folder_id, count(*) as count')
            ->groupBy('folder_id')
            ->pluck('count', 'folder_id');

        $uncategorized = MediaAsset::whereNull('folder_id')->count();

        return response()->json([
            'folders' => $folders,
            'counts' => $counts,
            'uncategorized' => $uncategorized,
        ]);
    }

    public function createFolder(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|integer|exists:media_folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $name = trim($request->input('name'));
        $slug = Str::slug($name);
        if (!$slug) {
            $slug = 'folder';
        }

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (MediaFolder::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $folder = MediaFolder::create([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $request->input('parent_id'),
            'user_id' => Auth::id(),
            'order' => MediaFolder::where('parent_id', $request->input('parent_id'))->max('order') + 1,
        ]);

        return response()->json(['message' => 'Folder created', 'folder' => $folder], 201);
    }

    public function updateFolder(Request $request, $id)
    {
        $this->ensureAdmin();

        $folder = MediaFolder::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'parent_id' => 'nullable|integer|exists:media_folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $folder->name = trim($request->input('name'));
        }

        if ($request->has('parent_id')) {
            // Prevent moving folder into itself or its descendants
            $newParent = $request->input('parent_id');
            if ($newParent && $this->isDescendant($folder->id, $newParent)) {
                return response()->json(['message' => 'Cannot move folder into its own descendant'], 422);
            }
            $folder->parent_id = $newParent;
        }

        $folder->save();

        return response()->json(['message' => 'Folder updated', 'folder' => $folder]);
    }

    public function deleteFolder($id)
    {
        $this->ensureAdmin();

        $folder = MediaFolder::findOrFail($id);

        // Move all assets in this folder to uncategorized
        MediaAsset::where('folder_id', $folder->id)->update(['folder_id' => null]);

        // Move child folders to parent
        MediaFolder::where('parent_id', $folder->id)->update(['parent_id' => $folder->parent_id]);

        $folder->delete();

        return response()->json(['message' => 'Folder deleted']);
    }

    private function isDescendant($folderId, $targetId): bool
    {
        if ($folderId == $targetId) return true;
        $folder = MediaFolder::find($targetId);
        while ($folder && $folder->parent_id) {
            if ($folder->parent_id == $folderId) return true;
            $folder = MediaFolder::find($folder->parent_id);
        }
        return false;
    }

    // ─────────────────────────────────────────────────────────────
    // MEDIA ASSETS
    // ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->ensureAdmin();

        $type = $request->string('type')->toString();
        $search = $request->string('search')->toString();
        $folderId = $request->input('folder_id'); // 'null' string means uncategorized, empty means all
        $query = MediaAsset::query()->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', '%' . $search . '%');
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        // Filter by folder
        if ($folderId === 'null' || $folderId === 'uncategorized') {
            $query->whereNull('folder_id');
        } elseif ($folderId !== null && $folderId !== '' && $folderId !== 'all') {
            $query->where('folder_id', (int) $folderId);
        }

        return response()->json($query->paginate(24));
    }

    public function uploadImage(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'folder_id' => 'nullable|integer|exists:media_folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return response()->json([
                'message' => 'Image conversion is not available on this server (GD/webp).',
            ], 500);
        }

        $file = $request->file('image');
        $contents = @file_get_contents($file->getRealPath());
        if ($contents === false) {
            return response()->json(['message' => 'Failed to read uploaded file'], 422);
        }

        $image = @imagecreatefromstring($contents);
        if ($image === false) {
            return response()->json(['message' => 'Unsupported image format'], 422);
        }

        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }

        // Preserve transparency for PNG
        if (function_exists('imagesavealpha')) {
            imagesavealpha($image, true);
            imagealphablending($image, true);
        }

        ob_start();
        imagewebp($image, null, 82);
        $webpData = ob_get_clean();
        imagedestroy($image);

        if (!is_string($webpData) || $webpData === '') {
            return response()->json(['message' => 'Failed to convert image to WebP'], 500);
        }

        $originalName = $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = Str::slug($baseName);
        if (!$slug) {
            $slug = 'image';
        }

        $dir = 'media/images/' . date('Y/m');
        $path = $dir . '/' . $slug . '-' . Str::random(10) . '.webp';
        Storage::disk('public')->put($path, $webpData);
        $url = Storage::disk('public')->url($path);

        $folderId = $request->input('folder_id');

        $asset = MediaAsset::create([
            'user_id' => Auth::id(),
            'folder_id' => $folderId ? (int) $folderId : null,
            'type' => 'image',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'original_name' => $originalName,
            'mime_type' => 'image/webp',
            'size' => strlen($webpData),
        ]);

        return response()->json([
            'message' => 'Uploaded',
            'asset' => $asset,
        ], 201);
    }

    public function uploadVideo(Request $request)
    {
        $this->ensureAdmin();

        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimes:mp4,webm,mov,avi,mkv|max:204800', // 200MB
            'folder_id' => 'nullable|integer|exists:media_folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('video');
        $originalName = $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'mp4';
        $slug = Str::slug($baseName) ?: 'video';

        $dir = 'media/videos/' . date('Y/m');
        $filename = $slug . '-' . Str::random(10) . '.' . $ext;
        $path = $file->storeAs($dir, $filename, 'public');
        $url = Storage::disk('public')->url($path);

        $folderId = $request->input('folder_id');

        $asset = MediaAsset::create([
            'user_id' => Auth::id(),
            'folder_id' => $folderId ? (int) $folderId : null,
            'type' => 'video',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType() ?: 'video/mp4',
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Video uploaded',
            'asset' => $asset,
        ], 201);
    }

    public function updateAsset(Request $request, $id)
    {
        $this->ensureAdmin();

        $asset = MediaAsset::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'folder_id' => 'nullable|integer|exists:media_folders,id',
            'original_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        if ($request->has('folder_id')) {
            $asset->folder_id = $request->input('folder_id') ?: null;
        }

        if ($request->has('original_name')) {
            $asset->original_name = $request->input('original_name');
        }

        $asset->save();

        return response()->json(['message' => 'Updated', 'asset' => $asset]);
    }

    public function deleteAsset($id)
    {
        $this->ensureAdmin();

        $asset = MediaAsset::findOrFail($id);

        // Delete file from storage
        if ($asset->path && $asset->disk) {
            Storage::disk($asset->disk)->delete($asset->path);
        }

        $asset->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
