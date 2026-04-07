<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Split Cash') }}
        </h2>
    </x-slot>

    <div class="py-12 flex justify-center">
        <div class="max-w-3xl w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-[2rem] p-8 surface-panel">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center size-16 rounded-[1rem] mb-4 bg-emerald-100 text-emerald-700">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-3xl font-extrabold text-slate-800 tracking-tight">Split Cash</h3>
                    <p class="mt-2 text-slate-500">Membagi uang tunai secara acak dengan hasil yang pas dan bulat.</p>
                </div>

                <div x-data="splitCash()" class="space-y-6">
                    <div class="form-field">
                        <label for="amountDisplay" class="form-label text-slate-700 block mb-2 font-bold">Total Uang (Rp)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-slate-500 font-semibold">Rp</span>
                            </div>
                            <input 
                                type="text" 
                                id="amountDisplay" 
                                x-model="amountDisplay" 
                                @input="formatAmount"
                                class="form-input pl-12 text-lg font-semibold" 
                                placeholder="0"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="peopleCount" class="form-label text-slate-700 block mb-2 font-bold">Jumlah Orang</label>
                        <select 
                            id="peopleCount" 
                            x-model.number="peopleCount" 
                            class="form-input text-lg font-semibold cursor-pointer text-slate-700"
                        >
                            <option value="2">2 Orang</option>
                            <option value="3">3 Orang</option>
                            <option value="4">4 Orang</option>
                            <option value="5">5 Orang</option>
                            <option value="6">6 Orang</option>
                        </select>
                    </div>

                    <div class="pt-2">
                        <button 
                            type="button" 
                            @click="calculateSplit" 
                            class="primary-action w-full text-lg py-4"
                        >
                            Proses Pembagian
                        </button>
                    </div>

                    <div x-show="results.length > 0" x-collapse x-cloak class="mt-8">
                        <div class="bg-gray-50 rounded-[1.5rem] p-6 border border-gray-200">
                            <h4 class="text-lg font-bold text-slate-800 mb-4 divider font-bold">Hasil Pembagian</h4>
                            
                            <div class="space-y-3">
                                <template x-for="(val, index) in results" :key="index">
                                    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:border-emerald-200 transition-colors">
                                        <div class="flex items-center gap-4">
                                            <div class="size-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                                                <span x-text="'#' + (index + 1)"></span>
                                            </div>
                                            <span class="font-bold text-slate-700 text-lg" x-text="formatCurrency(val)"></span>
                                        </div>
                                        <button 
                                            @click="copyToClipboard(val, index)"
                                            class="p-2 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 focus:outline-none transition-colors"
                                            title="Salin nominal"
                                        >
                                            <svg x-show="copiedIndex !== index" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg x-show="copiedIndex === index" x-cloak class="size-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-200 flex justify-between items-center px-2">
                                <span class="font-semibold text-slate-500">Total</span>
                                <span class="font-extrabold text-xl text-emerald-600" x-text="formatCurrency(totalValue)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('alpine:init', () => {
                        Alpine.data('splitCash', () => ({
                            amountDisplay: '',
                            rawAmount: 0,
                            peopleCount: 4,
                            results: [],
                            copiedIndex: null,

                            formatAmount() {
                                // Remove non-numeric chars
                                let val = this.amountDisplay.replace(/\D/g, '');
                                if (!val) {
                                    this.amountDisplay = '';
                                    this.rawAmount = 0;
                                    return;
                                }
                                
                                this.rawAmount = parseInt(val, 10);
                                this.amountDisplay = this.rawAmount.toLocaleString('id-ID');
                            },

                            formatCurrency(val) {
                                return 'Rp ' + val.toLocaleString('id-ID');
                            },

                            get totalValue() {
                                return this.results.reduce((acc, curr) => acc + curr, 0);
                            },

                            calculateSplit() {
                                if (this.rawAmount < this.peopleCount * 1000) {
                                    alert('Jumlah uang terlalu kecil untuk dibagi ke ' + this.peopleCount + ' orang.');
                                    return;
                                }

                                const total = this.rawAmount;
                                const n = this.peopleCount;
                                const mean = total / n;
                                
                                // Variation of max 25% from mean
                                const maxVar = mean * 0.25; 

                                let parts = [];
                                let currentSum = 0;

                                // Generate random parts
                                for (let i = 0; i < n - 1; i++) {
                                    // Make sure it balances somewhat
                                    let randVar = (Math.random() * 2 - 1) * maxVar;
                                    let partVal = mean + randVar;
                                    
                                    // Round to nearest 1000
                                    partVal = Math.floor(partVal / 1000) * 1000;
                                    
                                    // Prevent zero or negative if money is small
                                    if(partVal <= 0) partVal = 1000; 

                                    parts.push(partVal);
                                    currentSum += partVal;
                                }

                                // Last part handles the remainder
                                let lastPart = total - currentSum;
                                
                                // In some edge cases, last part could be negative or unrounded nicely.
                                // But since previous parts are kelipatan 1000 and total might not be (e.g. 10.500)
                                // We fix the discrepancy gracefully. Any remaining modulo of 1000 stays in lastPart.
                                
                                // To strictly enforce "kelipatan 1000" overall, 
                                // if total itself is not kelipatan 1000, the last part will just take the rest.
                                parts.push(lastPart);

                                // Shuffle the array so the last person doesn't always get the weird remaining
                                for (let i = parts.length - 1; i > 0; i--) {
                                    const j = Math.floor(Math.random() * (i + 1));
                                    [parts[i], parts[j]] = [parts[j], parts[i]];
                                }

                                this.results = parts;
                                this.copiedIndex = null;
                            },

                            async copyToClipboard(val, index) {
                                try {
                                    await navigator.clipboard.writeText(val.toString());
                                    this.copiedIndex = index;
                                    setTimeout(() => {
                                        if (this.copiedIndex === index) {
                                            this.copiedIndex = null;
                                        }
                                    }, 2000);
                                } catch (err) {
                                    console.error('Failed to copy: ', err);
                                }
                            }
                        }));
                    });
                </script>
            </div>
        </div>
    </div>
</x-app-layout>
