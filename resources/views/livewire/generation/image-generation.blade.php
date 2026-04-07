<div class="space-y-6">
    <div class="app-card">
        <h3 class="app-card__header mb-4">Text to Image Generation</h3>
        
        <form wire:submit.prevent="generateImage" class="space-y-4">
            <div>
                <label for="prompt" class="block text-sm font-medium text-[rgb(var(--app-muted))] mb-1">Prompt</label>
                <textarea 
                    id="prompt" 
                    wire:model="prompt" 
                    rows="3" 
                    class="app-input w-full" 
                    placeholder="Describe what you want to generate (e.g. A suited raccoon smoking a cigar)"
                    required
                ></textarea>
                @error('prompt') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="imageSize" class="block text-sm font-medium text-[rgb(var(--app-muted))] mb-1">Image Size</label>
                    <select id="imageSize" wire:model="imageSize" class="app-input w-full">
                        <option value="square_hd">Square HD (1024x1024)</option>
                        <option value="square">Square (512x512)</option>
                        <option value="portrait_3_4">Portrait (768x1024)</option>
                        <option value="portrait_9_16">Portrait (576x1024)</option>
                        <option value="landscape_4_3">Landscape (1024x768)</option>
                        <option value="landscape_16_9">Landscape (1024x576)</option>
                    </select>
                    @error('imageSize') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-4 flex items-center justify-between">
                <div>
                    @if (session()->has('error'))
                        <span class="text-red-500 text-sm">{{ session('error') }}</span>
                    @endif
                    @if (session()->has('info'))
                        <span class="text-blue-500 text-sm">{{ session('info') }}</span>
                    @endif
                </div>
                <button 
                    type="submit" 
                    class="app-btn-primary" 
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="generateImage">Generate Image</span>
                    <span wire:loading wire:target="generateImage" class="flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generating...
                    </span>
                </button>
            </div>
        </form>
    </div>

    @if($taskId)
        <div class="app-card" wire:poll.3s="checkTaskStatus">
            <div class="flex items-center gap-3">
                <svg class="animate-spin h-5 w-5 text-[rgb(var(--app-primary))]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Task is processing... Status: <strong>{{ $taskStatus }}</strong>. Task ID: <span class="font-mono text-sm text-[rgb(var(--app-muted))]">{{ $taskId }}</span></span>
            </div>
        </div>
    @endif

    @if(count($images) > 0)
    <div class="app-card">
        <h3 class="app-card__header mb-4">Generated Images</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($images as $image)
                <div class="group relative bg-gray-100 rounded-xl overflow-hidden shadow-sm hover:shadow-md border border-[rgb(var(--app-border))] transition-all">
                    <img src="{{ $image }}" class="w-full h-auto block object-contain">
                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-3 backdrop-blur-sm p-4 text-center">
                        <a href="{{ $image }}" target="_blank" class="app-btn bg-white text-gray-900 border-0 hover:bg-gray-100 transition duration-200 px-6">
                            Buka / Download
                        </a>
                        <p class="text-[10px] text-gray-300 bg-black/50 px-2 py-1 rounded w-full truncate border border-white/10" title="{{ $image }}">
                            {{ $image }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(count($taskHistory) > 0)
    <div class="app-card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="app-card__header">Tasks History</h3>
            <button wire:click="loadTaskHistory" class="text-sm text-[rgb(var(--app-primary))] hover:underline flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Refresh
            </button>
        </div>
        
        @if (session()->has('error_history'))
            <div class="text-red-500 text-sm mb-4">{{ session('error_history') }}</div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-[rgb(var(--app-muted))] border-b border-[rgb(var(--app-border))] pb-2">
                    <tr>
                        <th class="px-4 py-3 font-medium">Task ID</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgb(var(--app-border))]">
                    @foreach($taskHistory as $task)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-[rgb(var(--app-muted))]">{{ $task['task_id'] ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $status = $task['status'] ?? 'N/A';
                                    $statusClass = match($status) {
                                        'COMPLETED' => 'bg-green-100 text-green-700',
                                        'IN_PROGRESS', 'CREATED' => 'bg-blue-100 text-blue-700',
                                        'FAILED', 'ERROR' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if(in_array($status, ['CREATED', 'IN_PROGRESS']))
                                    <button wire:click="$set('taskId', '{{ $task['task_id'] }}')" class="text-[rgb(var(--app-primary))] hover:underline text-xs font-medium">Track</button>
                                @elseif($status === 'COMPLETED')
                                    <button wire:click="$set('taskId', '{{ $task['task_id'] }}')" class="text-[rgb(var(--app-primary))] hover:underline text-xs font-medium">View</button>
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
