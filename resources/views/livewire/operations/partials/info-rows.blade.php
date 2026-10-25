@props([
    'heading' => null,
    'rows' => [],
])

<section class="surface-panel">
    @if (filled($heading))
        <div class="surface-panel__header">
            <div>
                <p class="section-kicker">Info System</p>
                <h3>{{ $heading }}</h3>
            </div>
        </div>
    @endif

    <dl class="sysinfo-rows">
        @foreach ($rows as $row)
            <div class="sysinfo-rows__row">
                <dt>{{ $row['label'] }}</dt>
                <dd>{{ $row['value'] }}</dd>
            </div>
        @endforeach
    </dl>
</section>
