@props(['k' => '', 'v' => '-', 'col' => 3])

<div class="col-md-{{ $col }} mb-2">
    <div class="text-muted small">{{ $k }}</div>
    <div class="font-weight-bold">{{ $v ?: '—' }}</div>
</div>
