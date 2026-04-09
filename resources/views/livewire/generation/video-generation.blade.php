<div class="space-y-6">
    <div class="app-card">
        <h3 class="app-card__header mb-4">Text to Video Generation</h3>

        <form wire:submit.prevent="generateVideo" class="space-y-4">
            <div>
                <label for="video_prompt" class="mb-1 block text-sm font-medium text-[rgb(var(--app-muted))]">Prompt</label>
                <textarea
                    id="video_prompt"
                    wire:model="prompt"
                    rows="4"
                    class="app-input w-full"
                    placeholder="Describe motion, camera angle, subject, and atmosphere for the video you want to generate"
                    required
                ></textarea>
                @error('prompt') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="aspectRatio" class="mb-1 block text-sm font-medium text-[rgb(var(--app-muted))]">Aspect Ratio</label>
                    <select id="aspectRatio" wire:model="aspectRatio" class="app-input w-full">
                        @foreach ($aspectRatioOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('aspectRatio') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="duration" class="mb-1 block text-sm font-medium text-[rgb(var(--app-muted))]">Duration</label>
                    <select id="duration" wire:model="duration" class="app-input w-full">
                        @foreach ($durationOptions as $option)
                            <option value="{{ $option }}">{{ $option }}s</option>
                        @endforeach
                    </select>
                    @error('duration') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="cfgScale" class="mb-1 block text-sm font-medium text-[rgb(var(--app-muted))]">CFG Scale</label>
                    <input id="cfgScale" type="number" min="0" max="1" step="0.1" wire:model="cfgScale" class="app-input w-full" />
                    @error('cfgScale') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-end">
                    <label class="flex w-full items-center gap-3 rounded-xl border border-[rgb(var(--app-border))] px-4 py-3 text-sm text-[rgb(var(--app-ink))]">
                        <input type="checkbox" wire:model="generateAudio" class="rounded border-[rgb(var(--app-border))] text-[rgb(var(--app-primary))]" />
                        <span>Generate audio</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="negativePrompt" class="mb-1 block text-sm font-medium text-[rgb(var(--app-muted))]">Negative Prompt</label>
                <textarea
                    id="negativePrompt"
                    wire:model="negativePrompt"
                    rows="2"
                    class="app-input w-full"
                    placeholder="blur, distort, and low quality"
                ></textarea>
                @error('negativePrompt') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between pt-4">
                <div class="space-x-3">
                    @if (session()->has('error'))
                        <span class="text-sm text-red-500">{{ session('error') }}</span>
                    @endif
                    @if (session()->has('info'))
                        <span class="text-sm text-blue-500">{{ session('info') }}</span>
                    @endif
                    @if (session()->has('success'))
                        <span class="text-sm text-green-600">{{ session('success') }}</span>
                    @endif
                </div>

                <button type="submit" class="app-btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="generateVideo">Generate Video</span>
                    <span wire:loading wire:target="generateVideo" class="flex items-center gap-2">
                        <svg class="-ml-1 mr-3 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generating...
                    </span>
                </button>
            </div>
        </form>
    </div>

    @if ($taskId)
        <div class="app-card" wire:poll.3s="checkTaskStatus">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 animate-spin text-[rgb(var(--app-primary))]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Task is processing... Status: <strong>{{ $taskStatus }}</strong>. Task ID: <span class="font-mono text-sm text-[rgb(var(--app-muted))]">{{ $taskId }}</span></span>
            </div>
        </div>
    @endif

    @if (count($videos) > 0)
        <div class="app-card">
            <h3 class="app-card__header mb-4">Generated Videos</h3>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @foreach ($videos as $video)
                    <div class="overflow-hidden rounded-xl border border-[rgb(var(--app-border))] bg-black shadow-sm">
                        <video src="{{ $video }}" controls preload="metadata" class="aspect-video w-full bg-black"></video>
                        <div class="flex items-center justify-between gap-3 border-t border-white/10 px-4 py-3 text-sm">
                            <p class="truncate text-[rgb(var(--app-muted))]" title="{{ $video }}">{{ $video }}</p>
                            <a href="{{ $video }}" target="_blank" class="app-btn border-0 bg-white text-gray-900 transition duration-200 hover:bg-gray-100">
                                Buka / Download
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if (count($taskHistory) > 0)
        <div class="app-card">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="app-card__header">Tasks History</h3>
                <button wire:click="refreshTaskHistory" class="flex items-center gap-1 text-sm text-[rgb(var(--app-primary))] hover:underline">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh
                </button>
            </div>

            @if (session()->has('error_history'))
                <div class="mb-4 text-sm text-red-500">{{ session('error_history') }}</div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="border-b border-[rgb(var(--app-border))] pb-2 text-[rgb(var(--app-muted))]">
                        <tr>
                            <th class="px-4 py-3 font-medium">Task ID</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgb(var(--app-border))]">
                        @foreach ($taskHistory as $task)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-[rgb(var(--app-muted))]">{{ $task['task_id'] ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $status = $task['status'] ?? 'N/A';
                                        $statusClass = match ($status) {
                                            'COMPLETED' => 'bg-green-100 text-green-700',
                                            'IN_PROGRESS', 'CREATED' => 'bg-blue-100 text-blue-700',
                                            'FAILED', 'ERROR' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if (in_array($status, ['CREATED', 'IN_PROGRESS', 'COMPLETED'], true))
                                        <button wire:click="$set('taskId', '{{ $task['task_id'] }}')" class="text-xs font-medium text-[rgb(var(--app-primary))] hover:underline">
                                            {{ $status === 'COMPLETED' ? 'View' : 'Track' }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
