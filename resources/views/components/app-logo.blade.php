@props([])
{{-- Logo Syifa Global Group. Sumbernya diatur di App\Support\Brand::logoUrl()
     (file resmi di public/images/syifa-logo.* bila ada, kalau tidak pakai mark bawaan). --}}
<img src="{{ \App\Support\Brand::logoUrl() }}" alt="Syifa Global Group" {{ $attributes }}>
