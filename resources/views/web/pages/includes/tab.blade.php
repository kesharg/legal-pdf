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




<div class="tab-content" id="nav-tabContent">
    <div class="tab-pane fade {{ $activeTab === 'nav-google' ? 'show active' : '' }}" id="nav-google" role="tabpanel"
         aria-labelledby="nav-google-tab">
        @include('includes.partials._googleForm')
    </div>
    <div class="tab-pane fade {{ $activeTab === 'nav-microsoft' ? 'show active' : '' }}" id="nav-microsoft" role="tabpanel"
         aria-labelledby="nav-microsoft-tab">
        @include('includes.partials._microSoftForm')
    </div>
    @if(LaravelGmail::check() || session('userName'))
        <div class="tab-pane fade" id="nav-status" role="tabpanel"
             aria-labelledby="nav-status-tab">
            @include('includes.partials._status')
        </div>
    @endif
    @if (!LaravelGmail::check() || !session('userName'))
        <div class="tab-pane fade" id="nav-social" role="tabpanel"
             aria-labelledby="nav-status-tab">
            @include('includes.partials._sociallogin')
        </div>
    @endif
</div>
