<div class="settings-stack">
    <section class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <p class="section-kicker">Provider models</p>
                <h3>Model LLM</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    Kelola model yang muncul di form ChatBot untuk OpenAI, Gemini, Claude, dan Perplexity.
                </p>
            </div>
        </div>

        @if (session('llm_model_status'))
            <div class="form-alert form-alert--success">{{ session('llm_model_status') }}</div>
        @endif

        <button type="button" wire:click="openAdd" class="primary-action mt-6">Add model</button>

        @if ($showForm)
            <form wire:submit="save" class="settings-form">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="llm_provider" class="form-label">Provider</label>
                        <select id="llm_provider" wire:model="provider" class="form-input">
                            @foreach ($this->providers() as $providerOption)
                                <option value="{{ $providerOption }}">{{ $providerOption }}</option>
                            @endforeach
                        </select>
                        @error('provider') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="llm_name" class="form-label">Model name</label>
                        <input id="llm_name" type="text" wire:model="name" class="form-input" placeholder="gpt-4.1">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="llm_label" class="form-label">Label</label>
                        <input id="llm_label" type="text" wire:model="label" class="form-input" placeholder="GPT 4.1">
                        @error('label') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="llm_sort_order" class="form-label">Sort order</label>
                        <input id="llm_sort_order" type="number" min="0" wire:model="sortOrder" class="form-input">
                        @error('sortOrder') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field form-field--wide">
                        <div class="flex flex-wrap gap-5">
                            <label class="checkbox-row"><input type="checkbox" wire:model="supportsDocuments"> <span>Documents</span></label>
                            <label class="checkbox-row"><input type="checkbox" wire:model="supportsImages"> <span>Images</span></label>
                            <label class="checkbox-row"><input type="checkbox" wire:model="supportsWebSearch"> <span>Web search</span></label>
                            <label class="checkbox-row"><input type="checkbox" wire:model="isActive"> <span>Active</span></label>
                        </div>
                    </div>
                </div>

                <div class="form-actions gap-3">
                    <button type="button" wire:click="closeForm" class="rounded-2xl border border-[rgb(var(--app-line))] bg-white px-5 py-3 text-sm font-semibold text-[rgb(var(--app-ink))]">Cancel</button>
                    <button type="submit" class="primary-action">Save model</button>
                </div>
            </form>
        @endif

        <div class="mt-6 overflow-hidden rounded-2xl border border-[rgb(var(--app-line))]">
            <table class="w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                <thead class="bg-[rgb(var(--app-bg))]/70 text-xs uppercase tracking-[0.18em] text-[rgb(var(--app-muted))]">
                    <tr>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Model</th>
                        <th class="px-4 py-3">Capabilities</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-white/70">
                    @forelse ($models as $model)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $model->provider }}</td>
                            <td class="px-4 py-3">
                                <span class="block font-bold">{{ $model->label }}</span>
                                <span class="text-xs text-[rgb(var(--app-muted))]">{{ $model->name }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-[rgb(var(--app-muted))]">
                                {{ $model->supports_documents ? 'Docs' : '-' }} /
                                {{ $model->supports_images ? 'Images' : '-' }} /
                                {{ $model->supports_web_search ? 'Web' : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="status-pill {{ $model->is_active ? 'status-pill--ready' : 'status-pill--pending' }}">
                                    {{ $model->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-3">
                                    <button type="button" wire:click="openEdit({{ $model->id }})" class="text-sm font-semibold text-[rgb(var(--app-brand-deep))]">Edit</button>
                                    <button type="button" wire:click="toggleActive({{ $model->id }})" class="text-sm font-semibold text-[rgb(var(--app-brand-deep))]">Toggle</button>
                                    <button type="button" wire:click="delete({{ $model->id }})" wire:confirm="Hapus model ini?" class="text-sm font-semibold text-rose-600">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">Belum ada model.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
