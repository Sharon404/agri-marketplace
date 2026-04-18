@php
    $velzonJsDir = public_path('velzon/js');
    $velzonVendorJsDir = public_path('velzon/libs');
@endphp

@if (is_dir($velzonVendorJsDir))
    @foreach (glob($velzonVendorJsDir . '/*.js') ?: [] as $jsFile)
        <script src="/{{ ltrim(str_replace(public_path(), '', $jsFile), '/') }}"></script>
    @endforeach
@endif

@if (is_dir($velzonJsDir))
    @foreach (glob($velzonJsDir . '/*.js') ?: [] as $jsFile)
        <script src="/{{ ltrim(str_replace(public_path(), '', $jsFile), '/') }}"></script>
    @endforeach
@endif
