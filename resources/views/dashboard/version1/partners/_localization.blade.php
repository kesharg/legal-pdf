<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card custom-card">
            <div class="card-body">
                <h4 class="card-title">{{ localize("Localization") }}</h4>
                <br />
                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="country_id" :isRequired="true">{{ localize('Country') }}</x-form.label>
                            <x-form.select name="country_id" id="country_id" class="form-control">
                                <option value=""> Select Country </option>
                                @forelse($countries as $country)
                                <option value="{{ $country->id }}" @if (isset($user) && $country->id ==
                                    $user->country_id) selected @endif
                                    data-sub-domain-prefix="{{ $country->sub_domain_prefix }}"
                                    data-currency-code="{{ $country->currencies->code }}"
                                    data-currency-name="{{ $country->currencies->currency }}"
                                    data-currency-symbol="{{ $country->currencies->symbol }}"
                                    data-language-code="{{ $country->language->code }}"
                                    data-language-name="{{ $country->language->name }}">
                                    {{ $country->flag }} {{ $country->name }}
                                </option>
                                @empty
                                @endforelse
                            </x-form.select>
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="sub_domain_prefix" :isRequired="false">{{ localize('Subdomain Prefix')
                                }}</x-form.label>
                            <x-form.input type="text" id="sub_domain_prefix" name="sub_domain_prefix"
                                placeholder="Ex. us"
                                value="{{ isset($user) ? $user->sub_domain_prefix : old('sub_domain_prefix') }}"
                                readonly />
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="currency" :isRequired="false">{{ localize('Currency') }}</x-form.label>
                            <x-form.input type="text" id="currency" name="currency" placeholder="Ex. Pound Sterling"
                                value="{{ isset($user) ? $user->currency : old('currency') }}" readonly />
                            <input type="hidden" name="currency_code" id="currency_code"
                                value="{{ isset($user) ? $user->currency_code : '' }}" />
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="language" :isRequired="false">{{ localize('Language') }}</x-form.label>
                            <x-form.input type="text" id="language" name="language" placeholder="Ex. English"
                                value="{{ isset($user) ? $user->language : old('language') }}" readonly />
                            <input type="hidden" name="language_code" id="language_code"
                                value="{{ isset($user) ? $user->language_code : '' }}" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
