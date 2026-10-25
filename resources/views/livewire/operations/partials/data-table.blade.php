@props([
    'rows' => [],
    'columns' => [],
    'badgeColumns' => [],
    'badgeClasses' => [],
    'perPage' => 15,
    'placeholder' => 'Cari...',
    'emptyText' => 'Tidak ada data.',
])

@php
    $firstColumn = (string) (array_key_first($columns) ?? 'name');
@endphp

<div
    x-data="{
        search: '',
        sortKey: '{{ $firstColumn }}',
        sortAsc: true,
        page: 1,
        perPage: {{ (int) $perPage }},
        rows: {{ \Illuminate\Support\Js::from(array_values($rows)) }},
        badgeClasses: {{ \Illuminate\Support\Js::from((object) $badgeClasses) }},
        get filtered() {
            const query = this.search.toLowerCase().trim();

            const result = this.rows.filter((row) => ! query || Object.values(row).join(' ').toLowerCase().includes(query));

            result.sort((left, right) => {
                const a = String(left[this.sortKey] ?? '').toLowerCase();
                const b = String(right[this.sortKey] ?? '').toLowerCase();

                if (a === b) {
                    return 0;
                }

                return (a < b ? -1 : 1) * (this.sortAsc ? 1 : -1);
            });

            return result;
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },
        get currentPage() {
            return Math.min(this.page, this.totalPages);
        },
        get paged() {
            const start = (this.currentPage - 1) * this.perPage;

            return this.filtered.slice(start, start + this.perPage);
        },
        get rangeFrom() {
            return this.filtered.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
        },
        get rangeTo() {
            return Math.min(this.currentPage * this.perPage, this.filtered.length);
        },
        sortBy(column) {
            if (this.sortKey === column) {
                this.sortAsc = ! this.sortAsc;
            } else {
                this.sortKey = column;
                this.sortAsc = true;
            }

            this.page = 1;
        },
        badgeClass(value) {
            return this.badgeClasses[value] ?? 'sysinfo-badge';
        },
    }"
    class="sysinfo-table-wrap"
>
    <div class="sysinfo-table-toolbar">
        <input
            type="search"
            x-model.debounce.200ms="search"
            x-on:input="page = 1"
            placeholder="{{ $placeholder }}"
            class="sysinfo-table-search"
        />

        <div class="sysinfo-table-pager">
            <span x-text="`${rangeFrom}–${rangeTo} dari ${filtered.length}`"></span>

            <button type="button" class="sysinfo-table-btn" x-on:click="page = Math.max(1, page - 1)" x-bind:disabled="currentPage === 1">
                Sebelumnya
            </button>

            <button type="button" class="sysinfo-table-btn" x-on:click="page = Math.min(totalPages, page + 1)" x-bind:disabled="currentPage >= totalPages">
                Berikutnya
            </button>
        </div>
    </div>

    <div class="sysinfo-table-scroll">
        <table class="sysinfo-table">
            <thead>
                <tr>
                    @foreach ($columns as $key => $label)
                        <th scope="col">
                            <button type="button" x-on:click="sortBy('{{ $key }}')">
                                {{ $label }}
                                <span x-show="sortKey === '{{ $key }}'" x-text="sortAsc ? '▲' : '▼'"></span>
                            </button>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                <template x-for="(row, index) in paged" :key="index">
                    <tr>
                        @foreach ($columns as $key => $label)
                            <td>
                                @if (in_array($key, $badgeColumns, true))
                                    <span x-text="row['{{ $key }}']" :class="badgeClass(row['{{ $key }}'])"></span>
                                @else
                                    <span x-text="row['{{ $key }}']"></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </template>
            </tbody>
        </table>

        <p x-show="filtered.length === 0" x-cloak class="sysinfo-table-empty">
            {{ $emptyText }}
        </p>
    </div>
</div>
