<?php

namespace App\Services\Internet;

use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use RuntimeException;

class BookmarkService
{
    public function __construct(
        private readonly BookmarkPreviewService $previewService,
    ) {}

    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Bookmark::query()->where('user_id', $user->id);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('category_id', $filters)) {
            if ($filters['category_id'] === null || $filters['category_id'] === 'null') {
                $query->whereNull('category_id');
            } else {
                $query->where('category_id', $filters['category_id']);
            }
        }

        $sortBy = in_array($filters['sort_by'] ?? '', ['created_at', 'title', 'domain', 'visited_count'], true)
            ? $filters['sort_by']
            : 'created_at';

        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);

        return $query->with('category')->paginate($perPage);
    }

    public function create(User $user, array $data): Bookmark
    {
        $url = $this->previewService->normalizeUrl($data['url']);

        $exists = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('url', $url)
            ->exists();

        if ($exists) {
            throw new RuntimeException('URL sudah ada di bookmark.');
        }

        $metadata = $this->previewService->preview($url);

        $bookmark = Bookmark::query()->create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'url' => $url,
            'title' => $data['title'] ?? $metadata['title'],
            'description' => $data['description'] ?? $metadata['description'],
            'image_url' => $metadata['image_url'],
            'favicon_url' => $metadata['favicon_url'],
            'domain' => $metadata['domain'],
            'metadata' => $metadata,
        ]);

        return $bookmark->load('category');
    }

    public function update(Bookmark $bookmark, array $data): Bookmark
    {
        $this->assertOwnership($bookmark);

        $refetch = false;

        if (isset($data['url']) && $data['url'] !== $bookmark->url) {
            $url = $this->previewService->normalizeUrl($data['url']);

            $exists = Bookmark::query()
                ->where('user_id', $bookmark->user_id)
                ->where('url', $url)
                ->where('id', '!=', $bookmark->id)
                ->exists();

            if ($exists) {
                throw new RuntimeException('URL sudah ada di bookmark.');
            }

            $data['url'] = $url;
            $refetch = true;
        }

        if ($refetch) {
            $metadata = $this->previewService->preview($data['url']);

            $data['title'] ??= $metadata['title'];
            $data['description'] ??= $metadata['description'];
            $data['image_url'] ??= $metadata['image_url'];
            $data['favicon_url'] ??= $metadata['favicon_url'];
            $data['domain'] ??= $metadata['domain'];
            $data['metadata'] = $metadata;
        }

        $bookmark->update($data);

        return $bookmark->fresh()->load('category');
    }

    public function delete(Bookmark $bookmark): void
    {
        $this->assertOwnership($bookmark);

        $bookmark->delete();
    }

    public function categories(User $user): Collection
    {
        return $user->bookmarkCategories()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount('bookmarks')
            ->get();
    }

    public function createCategory(User $user, array $data): BookmarkCategory
    {
        return $user->bookmarkCategories()->create($data);
    }

    public function updateCategory(BookmarkCategory $category, array $data): BookmarkCategory
    {
        $this->assertCategoryOwnership($category);
        $category->update($data);

        return $category->fresh();
    }

    public function deleteCategory(BookmarkCategory $category): void
    {
        $this->assertCategoryOwnership($category);

        Bookmark::query()
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $category->delete();
    }

    private function assertOwnership(Bookmark $bookmark): void
    {
        if ((int) $bookmark->user_id !== (int) auth()->id()) {
            throw new RuntimeException('Bookmark tidak ditemukan.');
        }
    }

    private function assertCategoryOwnership(BookmarkCategory $category): void
    {
        if ((int) $category->user_id !== (int) auth()->id()) {
            throw new RuntimeException('Kategori tidak ditemukan.');
        }
    }
}
