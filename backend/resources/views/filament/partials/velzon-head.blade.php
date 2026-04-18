@php
    $velzonCssDir = public_path('velzon/css');
    $velzonVendorCssDir = public_path('velzon/libs');
@endphp

@if (is_dir($velzonVendorCssDir))
    @foreach (glob($velzonVendorCssDir . '/*.css') ?: [] as $cssFile)
        <link rel="stylesheet" href="/{{ ltrim(str_replace(public_path(), '', $cssFile), '/') }}">
    @endforeach
@endif

@if (is_dir($velzonCssDir))
    @foreach (glob($velzonCssDir . '/*.css') ?: [] as $cssFile)
        <link rel="stylesheet" href="/{{ ltrim(str_replace(public_path(), '', $cssFile), '/') }}">
    @endforeach
@endif
