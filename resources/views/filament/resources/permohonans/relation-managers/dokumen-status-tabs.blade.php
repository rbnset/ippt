@php
    $counts = $relationManager->getStatusTabCounts();
    $active = $relationManager->statusTab;

    $tabs = [
        'semua' => ['label' => 'Semua', 'icon' => 'heroicon-m-squares-2x2'],
        'menunggu' => ['label' => 'Menunggu', 'icon' => 'heroicon-m-clock'],
        'diterima' => ['label' => 'Diterima', 'icon' => 'heroicon-m-check-circle'],
        'ditolak' => ['label' => 'Ditolak', 'icon' => 'heroicon-m-x-circle'],
    ];
@endphp

<div class="px-4 pt-3">
    <x-filament::tabs label="Filter status dokumen" wire:key="dokumen-status-tabs">
        @foreach ($tabs as $key => $tab)
            <x-filament::tabs.item
                :active="$active === $key"
                :icon="$tab['icon']"
                wire:click="setStatusTab('{{ $key }}')"
            >
                {{ $tab['label'] }}

                <x-slot name="badge">
                    {{ $key === 'semua' ? array_sum($counts) : ($counts[$key] ?? 0) }}
                </x-slot>
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</div>
