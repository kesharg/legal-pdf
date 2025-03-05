
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card custom-card">
            <div class="top-left"></div>
            <div class="top-right"></div>
            <div class="bottom-left"></div>
            <div class="bottom-right"></div>
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <h4 class="card-title">{{ localize('Currency Information') }}</h4>
                </div>
            </div>
            <div class="card-body">

                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="currency"
                                :isRequired="false">{{ localize('Currency Name') }}</x-form.label>
                            <x-form.input type="text" id="currency" name="currency" placeholder="Ex. GB"
                                value="{{ isset($currency) ? $currency->currency : old('currency') }}" />
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="code"
                                :isRequired="false">{{ localize('Code') }} (Thre Characters)</x-form.label>
                            <x-form.input type="text" id="code" name="code" placeholder="Ex. GB"
                                value="{{ isset($currency) ? $currency->code : old('code') }}" />
                        </div>
                    </div>

                    <x-form.input type="hidden" id="minor_unit" name="minor_unit" value="{{ isset($currency) ? $currency->minor_unit : old('minor_unit') }}" />

                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="symbol"
                                :isRequired="false">{{ localize('Symbol') }}</x-form.label>
                            <x-form.input type="text" id="symbol" name="symbol" placeholder="Ex. GB"
                                value="{{ isset($currency) ? $currency->symbol : old('symbol') }}" />
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </div>
</div>

<x-form.submit />


@push('extra-scripts')
    <script type="text/javascript">
        $(document).ready(function() {

        });

        $(document).ready(function() {
        });
    </script>
@endpush
