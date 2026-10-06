@once
    <link rel="stylesheet" href="{{ asset('css/soopi-toast.css') }}?v=1">
    <div data-toast-config data-close="{{ __('Đóng thông báo') }}" data-label="{{ __('Thông báo') }}"></div>
    @foreach(['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info', 'status' => 'success', 'message' => 'info'] as $flashKey => $toastType)
        @if(is_string(session($flashKey)) && session($flashKey) !== '')
            <div data-toast-source="{{ $toastType }}" class="soopi-toast-fallback" role="status">{{ session($flashKey) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div data-toast-source="error" class="soopi-toast-fallback" role="alert">{{ __('Chưa thể hoàn tất thao tác. Vui lòng kiểm tra lại:') }} {{ $errors->first() }}</div>
    @endif
    <script src="{{ asset('js/soopi-toast.js') }}?v=2"></script>
@endonce
