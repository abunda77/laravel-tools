<?php

namespace App\Livewire\Settings;

use App\Models\LlmModel;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LlmModelManager extends Component
{
    public ?int $editingId = null;

    public string $provider = 'openai';

    public string $name = '';

    public string $label = '';

    public bool $supportsDocuments = true;

    public bool $supportsImages = true;

    public bool $supportsWebSearch = true;

    public bool $isActive = true;

    public int $sortOrder = 0;

    public bool $showForm = false;

    /**
     * @return array<int, string>
     */
    public function providers(): array
    {
        return ['openai', 'gemini', 'anthropic', 'perplexity'];
    }

    public function openAdd(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $model = LlmModel::query()->findOrFail($id);

        $this->editingId = $model->id;
        $this->provider = $model->provider;
        $this->name = $model->name;
        $this->label = $model->label;
        $this->supportsDocuments = $model->supports_documents;
        $this->supportsImages = $model->supports_images;
        $this->supportsWebSearch = $model->supports_web_search;
        $this->isActive = $model->is_active;
        $this->sortOrder = $model->sort_order;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'provider' => ['required', 'string', Rule::in($this->providers())],
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('llm_models', 'name')
                    ->where('provider', $this->provider)
                    ->ignore($this->editingId),
            ],
            'label' => ['required', 'string', 'max:190'],
            'supportsDocuments' => ['boolean'],
            'supportsImages' => ['boolean'],
            'supportsWebSearch' => ['boolean'],
            'isActive' => ['boolean'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        $attributes = [
            'provider' => $validated['provider'],
            'name' => $validated['name'],
            'label' => $validated['label'],
            'supports_documents' => $validated['supportsDocuments'],
            'supports_images' => $validated['supportsImages'],
            'supports_web_search' => $validated['supportsWebSearch'],
            'is_active' => $validated['isActive'],
            'sort_order' => $validated['sortOrder'],
        ];

        if ($this->editingId !== null) {
            LlmModel::query()->findOrFail($this->editingId)->update($attributes);
        } else {
            LlmModel::query()->create($attributes);
        }

        $this->closeForm();

        session()->flash('llm_model_status', 'Model LLM berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $model = LlmModel::query()->findOrFail($id);
        $model->update(['is_active' => ! $model->is_active]);
    }

    public function delete(int $id): void
    {
        LlmModel::query()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->closeForm();
        }

        session()->flash('llm_model_status', 'Model LLM berhasil dihapus.');
    }

    public function render(): View
    {
        return view('livewire.settings.llm-model-manager', [
            'models' => LlmModel::query()
                ->orderBy('provider')
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->provider = 'openai';
        $this->name = '';
        $this->label = '';
        $this->supportsDocuments = true;
        $this->supportsImages = true;
        $this->supportsWebSearch = true;
        $this->isActive = true;
        $this->sortOrder = 0;

        $this->resetValidation();
    }
}
