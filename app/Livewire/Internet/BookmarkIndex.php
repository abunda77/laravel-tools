<?php

namespace App\Livewire\Internet;

use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Services\Internet\BookmarkPreviewService;
use App\Services\Internet\BookmarkService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class BookmarkIndex extends Component
{
    use WithPagination;

    #[Validate(['required', 'string', 'max:2048'])]
    public string $url = '';

    #[Validate(['nullable', 'string', 'max:1000'])]
    public ?string $description = null;

    public ?int $categoryId = null;

    public ?array $preview = null;

    public bool $previewLoading = false;

    public ?string $previewError = null;

    public string $search = '';

    public ?int $filterCategoryId = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $showCategoryForm = false;

    #[Validate(['required', 'string', 'max:100'])]
    public string $categoryName = '';

    #[Validate(['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'])]
    public ?string $categoryColor = null;

    #[Computed]
    public function categories()
    {
        return BookmarkCategory::query()
            ->where('user_id', auth()->id())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount('bookmarks')
            ->get();
    }

    public function updatedUrl(): void
    {
        $this->reset('preview', 'previewError');

        $url = trim($this->url);

        if (empty($url)) {
            return;
        }

        $this->previewLoading = true;

        try {
            $service = app(BookmarkPreviewService::class);
            $metadata = $service->preview($url);

            $alreadySaved = Bookmark::query()
                ->where('user_id', auth()->id())
                ->where('url', $metadata['url'])
                ->exists();

            $metadata['already_saved'] = $alreadySaved;
            $this->preview = $metadata;
        } catch (\Throwable $e) {
            $this->previewError = $e->getMessage();
        } finally {
            $this->previewLoading = false;
        }
    }

    public function save(): void
    {
        $this->reset('successMessage', 'errorMessage');

        $this->validate();

        try {
            $service = app(BookmarkService::class);
            $service->create(auth()->user(), [
                'url' => $this->url,
                'category_id' => $this->categoryId,
                'description' => $this->description,
            ]);

            $this->reset('url', 'description', 'categoryId', 'preview', 'previewError');
            $this->successMessage = 'Bookmark berhasil disimpan.';
            $this->dispatch('bookmark-saved');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function delete(int $bookmarkId): void
    {
        $bookmark = Bookmark::find($bookmarkId);

        if (! $bookmark || (int) $bookmark->user_id !== (int) auth()->id()) {
            return;
        }

        $bookmark->delete();
        $this->successMessage = 'Bookmark berhasil dihapus.';
    }

    public function saveCategory(): void
    {
        $this->validateOnly('categoryName');

        BookmarkCategory::query()->create([
            'user_id' => auth()->id(),
            'name' => $this->categoryName,
            'color' => $this->categoryColor,
        ]);

        $this->reset('categoryName', 'categoryColor', 'showCategoryForm');
        $this->successMessage = 'Kategori berhasil ditambahkan.';
        unset($this->categories);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = BookmarkCategory::find($categoryId);

        if (! $category || (int) $category->user_id !== (int) auth()->id()) {
            return;
        }

        Bookmark::query()->where('category_id', $category->id)->update(['category_id' => null]);
        $category->delete();

        $this->successMessage = 'Kategori berhasil dihapus.';
        unset($this->categories);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Bookmark::query()
            ->where('user_id', auth()->id())
            ->with('category');

        if (! empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if ($this->filterCategoryId !== null) {
            $query->where('category_id', $this->filterCategoryId);
        }

        $query->orderBy('created_at', 'desc');

        return view('livewire.internet.bookmark', [
            'bookmarks' => $query->paginate(20),
        ]);
    }
}
