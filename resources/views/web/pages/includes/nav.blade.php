@php
    // Determine the active tab based on the 'platform' input
    if (isset($order->platform_type) && $order->platform_type === 1) {
        $activeTab = 'nav-google';
    } elseif(isset($order->platform_type) && $order->platform_type === 2) {
        $activeTab = 'nav-microsoft';
    } else {
        $activeTab = 'nav-google';
    }
@endphp

<nav>
    <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <div class="nav-link {{ $activeTab === 'nav-google' ? 'active' : '' }}" id="nav-google-tab" data-bs-toggle="tab" data-bs-target="#nav-google" type="button" role="tab" aria-controls="nav-google" aria-selected="{{ $activeTab === 'nav-google' ? 'true' : 'false' }}">
                <img src="{{ asset('web/assets/img/gmail.png') }}" alt="gmail"> Gmail
            </div>
            <div class="nav-link {{ $activeTab === 'nav-microsoft' ? 'active' : '' }}" id="nav-microsoft-tab" data-bs-toggle="tab" data-bs-target="#nav-microsoft" type="button" role="tab" aria-controls="nav-microsoft" aria-selected="{{ $activeTab === 'nav-microsoft' ? 'true' : 'false' }}">
                <img src="{{ asset('web/assets/img/outlook.png') }}" alt="gmail"> Outlook
            </div>

        @if(LaravelGmail::check() || session('userName'))
            <div class="nav-link" id="nav-status-tab" data-bs-toggle="tab" data-bs-target="#nav-status" type="button" role="tab" aria-controls="nav-microsoft" aria-selected="false">
                <img src="{{ asset('web/assets/img/status.png') }}" alt="status"> Status
            </div>
        @endif

        @if (getMainAccount() == null)
            <div class="nav-link d-none" id="nav-social-tab" data-bs-toggle="tab" data-bs-target="#nav-social" type="button" role="tab" aria-controls="nav-social" aria-selected="true">Login</div>
        @endif
    </div>
</nav>
