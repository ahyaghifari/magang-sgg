{{-- Footer kecil "Terakhir diubah oleh {nama} · {tanggal jam}". Props: change (InternShiftAssignment|null). --}}
@props(['change' => null])

@if ($change?->updater)
    <p {{ $attributes->merge(['class' => 'shift-lastchange']) }}>
        <i class="fa-solid fa-clock-rotate-left" style="margin-top:0.15rem;" aria-hidden="true"></i>
        <span>Terakhir diubah oleh <b>{{ $change->updater->name }}</b> · {{ $change->updated_at->locale('id')->translatedFormat('j M Y, H:i') }}</span>
    </p>
@endif
