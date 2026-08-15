<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Services\Internet\BookmarkPreviewService;
use App\Services\Internet\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class BookmarkController extends Controller
{
    public function __construct(
        private readonly BookmarkService $bookmarkService,
        private readonly BookmarkPreviewService $previewService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookmarks = $this->bookmarkService->list($request->user(), $request->only([
            'search', 'category_id', 'sort_by', 'sort_dir', 'per_page', 'page',
        ]));

        return response()->json($bookmarks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'category_id' => ['nullable', 'integer', Rule::exists('bookmark_categories', 'id')->where('user_id', $request->user()->id)],
            'title' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $bookmark = $this->bookmarkService->create($request->user(), $validated);

            return response()->json(['data' => $bookmark], 201);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function show(Request $request, Bookmark $bookmark): JsonResponse
    {
        if ((int) $bookmark->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Bookmark tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $bookmark->load('category')]);
    }

    public function update(Request $request, Bookmark $bookmark): JsonResponse
    {
        if ((int) $bookmark->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Bookmark tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'url' => ['nullable', 'string', 'max:2048'],
            'category_id' => ['nullable', 'integer', Rule::exists('bookmark_categories', 'id')->where('user_id', $request->user()->id)],
            'title' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $bookmark = $this->bookmarkService->update($bookmark, $validated);

            return response()->json(['data' => $bookmark]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function destroy(Request $request, Bookmark $bookmark): JsonResponse
    {
        if ((int) $bookmark->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Bookmark tidak ditemukan.'], 404);
        }

        $this->bookmarkService->delete($bookmark);

        return response()->json(null, 204);
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $metadata = $this->previewService->preview($validated['url']);

            $alreadySaved = Bookmark::query()
                ->where('user_id', $request->user()->id)
                ->where('url', $metadata['url'])
                ->exists();

            $metadata['already_saved'] = $alreadySaved;

            return response()->json(['data' => $metadata]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function categoriesIndex(Request $request): JsonResponse
    {
        $categories = $this->bookmarkService->categories($request->user());

        return response()->json(['data' => $categories]);
    }

    public function categoriesStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $category = $this->bookmarkService->createCategory($request->user(), $validated);

        return response()->json(['data' => $category], 201);
    }

    public function categoriesUpdate(Request $request, BookmarkCategory $category): JsonResponse
    {
        if ((int) $category->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Kategori tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = $this->bookmarkService->updateCategory($category, $validated);

        return response()->json(['data' => $category]);
    }

    public function categoriesDestroy(Request $request, BookmarkCategory $category): JsonResponse
    {
        if ((int) $category->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Kategori tidak ditemukan.'], 404);
        }

        $this->bookmarkService->deleteCategory($category);

        return response()->json(null, 204);
    }
}
