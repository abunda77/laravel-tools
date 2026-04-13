<?php

namespace App\Livewire\Settings;

use App\Models\ApiKey;
use Illuminate\View\View;
use Livewire\Component;

class ApiKeyManager extends Component
{
    // ── Form fields ──────────────────────────────────────────────────────────

    public string $name = '';

    public string $label = '';

    public string $description = '';

    public string $value = '';

    public bool $isActive = true;

    // ── State ────────────────────────────────────────────────────────────────

    /** @var int|null ID of the record being edited; null = adding new */
    public ?int $editingId = null;

    /** @var bool Show/hide the add-or-edit form panel */
    public bool $showForm = false;

    /** @var bool Whether the "value" field has a stored key (mask display) */
    public bool $hasValue = false;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        // nothing needed on mount
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function openAdd(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingId = null;
    }

    public function openEdit(int $id): void
    {
        $key = ApiKey::findOrFail($id);

        $this->editingId = $key->id;
        $this->name = $key->name;
        $this->label = $key->label;
        $this->description = (string) $key->description;
        $this->isActive = $key->is_active;
        $this->value = ''; // never pre-fill the secret
        $this->hasValue = filled($key->value);
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function save(): void
    {
        $isEditing = $this->editingId !== null;

        $rules = [
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'value' => ['nullable', 'string', 'max:2048'],
            'isActive' => ['boolean'],
        ];

        // name is only editable on create
        if (! $isEditing) {
            $rules['name'] = ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/', 'unique:api_keys,name'];
        }

        $validated = $this->validate($rules, [
            'name.regex' => 'Name hanya boleh mengandung huruf kecil, angka, dan underscore.',
        ]);

        if ($isEditing) {
            /** @var ApiKey $key */
            $key = ApiKey::findOrFail($this->editingId);

            $attributes = [
                'label' => $validated['label'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['isActive'],
            ];

            // Only update value if user typed something new
            if (filled($validated['value'])) {
                $attributes['value'] = $validated['value'];
                $this->hasValue = true;
            }

            $key->fill($attributes);
            $key->save();
        } else {
            $key = ApiKey::create([
                'name' => $validated['name'],
                'label' => $validated['label'],
                'description' => $validated['description'] ?? null,
                'value' => $validated['value'] ?: null,
                'is_active' => $validated['isActive'],
            ]);

            $this->hasValue = filled($key->value);
        }

        $this->closeForm();
        session()->flash('api_key_status', 'API key berhasil disimpan.');
    }

    public function clearValue(int $id): void
    {
        $key = ApiKey::findOrFail($id);
        $key->update(['value' => null]);

        if ($this->editingId === $id) {
            $this->hasValue = false;
        }

        session()->flash('api_key_status', "Value untuk [{$key->label}] dihapus.");
    }

    public function delete(int $id): void
    {
        $key = ApiKey::findOrFail($id);
        $label = $key->label;
        $key->delete();

        if ($this->editingId === $id) {
            $this->closeForm();
        }

        session()->flash('api_key_status', "API key [{$label}] dihapus.");
    }

    public function toggleActive(int $id): void
    {
        $key = ApiKey::findOrFail($id);
        $key->update(['is_active' => ! $key->is_active]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->name = '';
        $this->label = '';
        $this->description = '';
        $this->value = '';
        $this->isActive = true;
        $this->hasValue = false;
        $this->editingId = null;

        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.settings.api-key-manager', [
            'apiKeys' => ApiKey::query()->orderBy('label')->get(),
        ]);
    }
}
