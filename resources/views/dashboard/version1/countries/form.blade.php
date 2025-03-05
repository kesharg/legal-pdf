<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card custom-card">
            <div class="top-left"></div>
            <div class="top-right"></div>
            <div class="bottom-left"></div>
            <div class="bottom-right"></div>
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <h4 class="card-title">{{ localize('Country Information') }} : {{ $country->name }}</h4>
                </div>
            </div>
            <div class="card-body">

                <div class="row">
                    {{--<div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="name" :isRequired="true">{{ localize('Name') }}</x-form.label>
                            <x-form.input type="text" id="name" name="name" placeholder="Ex. United Kingdom"
                                value="{{ isset($country) ? $country->name : old('name') }}" />
                        </div>
                    </div>--}}

                    {{--<div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="flag" :isRequired="false">{{ localize('Flag') }}</x-form.label>
                            <x-form.select name="flag" id="flag" class="form-control">
                                <option value=""> Select Flag </option>
                                @forelse($flags as $flag)
                                    <option value="{{ $flag->flag }}"
                                        @if (isset($country) && $flag->flag == $country->flag) selected @endif>{{ $flag->flag }}</option>
                                @empty
                                @endforelse
                            </x-form.select>
                        </div>
                    </div>--}}

                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="code"
                                :isRequired="false">{{ localize('ISO Code') }} (Two characters)</x-form.label>
                            <x-form.input type="text" id="code" name="code" placeholder="Ex. GB"
                                value="{{ isset($country) ? $country->code : old('code') }}" />
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="language_code" :isRequired="false">{{ localize('Language') }}</x-form.label>
                            <x-form.select name="language_code" id="language_code" class="form-control select2">
                                <option value=""> Select Language </option>
                                @forelse($languages as $language)
                                    <option value="{{ $language->code }}"
                                        @if (isset($country) && $language->code == $country->language_code) selected @endif>{{ $language->name }}</option>
                                @empty
                                @endforelse
                            </x-form.select>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="currency" :isRequired="false">{{ localize('Currency') }}</x-form.label>
                            <x-form.select name="currency" id="currency" class="form-control select2">
                                <option value=""> Select Currency </option>
                                @forelse($currencies as $currency)
                                    <option value="{{ $currency->code }}"
                                        @if (isset($country) && $currency->code == $country->currency) selected @endif>{{ $currency->currency }}</option>
                                @empty
                                @endforelse
                            </x-form.select>
                        </div>
                    </div>


                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="sub_domain_prefix"
                                :isRequired="false">{{ localize('Sub Domain Prefix') }} (Two characters)</x-form.label>
                            <x-form.input type="text" id="sub_domain_prefix" name="sub_domain_prefix"
                                placeholder="Ex. us"
                                value="{{ isset($country) ? $country->sub_domain_prefix : old('sub_domain_prefix') }}" />
                        </div>
                    </div>

                    @if (isset($country))
                        <div class="col-lg-4">
                            <div class="form-group has-validation">
                                <x-form.label for="is_enable" :isRequired="true">{{ localize('Status') }}</x-form.label>

                                <x-form.select name="is_enable" class="form-control select2">
                                    <option value="1" @if ($country->is_enable == 1) selected @endif>
                                        {{ localize('Active') }}</option>
                                    <option value="0" @if ($country->is_enable == 0) selected @endif>
                                        {{ localize('Inactive') }}</option>

                                </x-form.select>
                            </div>
                        </div>
                    @endif

                </div>
            </div>


        </div>
    </div>
</div>

<x-form.submit />


@push('extra-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
@endpush
