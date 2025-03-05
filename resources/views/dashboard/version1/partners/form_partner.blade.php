<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="card custom-card">
            <x-form.submit />
        </div>
        <div class="card custom-card">
            <div class="top-left"></div>
            <div class="top-right"></div>
            <div class="bottom-left"></div>
            <div class="bottom-right"></div>
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <h4 class="card-title">{{ localize('Personal Information') }}</h4>
                </div>
            </div>
            <div class="card-body">

                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="firstName" :isRequired="true">{{ localize('First Name') }}</x-form.label>
                            <x-form.input type="text" id="firstName" name="first_name" placeholder="Ex. John"
                                value="{{ isset($user) ? $user->first_name : old('firstName') }}" />
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="lastName" :isRequired="false">{{ localize('Last Name') }}</x-form.label>
                            <x-form.input type="text" id="lastName" name="last_name" placeholder="Ex. John"
                                value="{{ isset($user) ? $user->last_name : old('last_name') }}" />
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="email" :isRequired="true">{{ localize('Email') }}</x-form.label>
                            <x-form.input type="email" id="email" name="email" placeholder="Ex. admin@example.com"
                                value="{{ isset($user) ? $user->email : old('email') }}" />
                        </div>
                    </div>

                    <div class="col-lg-3">
                        <div class="form-group has-validation">
                            <x-form.label for="mobile_no" :isRequired="false">{{ localize('Mobile') }}</x-form.label>
                            <x-form.input type="text" id="mobile_no" name="mobile_no" placeholder="+441234567890"
                                value="{{ isset($user) ? $user->mobile_no : old('mobile_no') }}" />
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Authentication Start --}}
@include("dashboard.version1.partners._authentication")
{{-- Authentication End --}}

{{-- Partner Business Information Start --}}
@include('dashboard.version1.partners._localization')
{{-- Partner Business Information End --}}

{{-- Partner Business Information Start --}}
@include('dashboard.version1.partners._business')
{{-- Partner Business Information End --}}

{{-- Partner Company Information Start --}}
{{-- @include("dashboard.partners._company") --}}
{{-- Partner Company Information End --}}

{{-- Partner Contact Start --}}
{{-- @include("dashboard.partners._account") --}}
{{-- Partner Contact End --}}

{{-- Partner Contact Start --}}
{{-- @include("dashboard.partners._contact") --}}
{{-- Partner Contact End --}}

{{-- Social Link Start --}}
{{-- @include("dashboard.partners._social") --}}
{{-- Social Link End --}}

{{-- Partner Attachment Start --}}
{{-- @include('dashboard.version1.partners._attachment') --}}
{{-- Partner Attachment End --}}

@include('dashboard.version1.partners._wallet')

@push('extra-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const countrySelect = document.getElementById('country_id');
        const subDomainPrefixInput = document.getElementById('sub_domain_prefix');
        const currencyInput = document.getElementById('currency');
        const currencyCodeInput = document.getElementById('currency_code');
        const languageInput = document.getElementById('language');
        const languageCodeInput = document.getElementById('language_code');
        const priceInput = document.getElementById('price');  // The price input

        countrySelect.addEventListener('change', function () {
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];

            // Get the data attributes from the selected option
            const subDomainPrefix = selectedOption.getAttribute('data-sub-domain-prefix');
            const currencyName = selectedOption.getAttribute('data-currency-name');
            const currencyCode = selectedOption.getAttribute('data-currency-code');
            const currencySymbol = selectedOption.getAttribute('data-currency-symbol');  // New symbol attribute
            const languageName = selectedOption.getAttribute('data-language-name');
            const languageCode = selectedOption.getAttribute('data-language-code');

            // Set the values to the corresponding input fields
            subDomainPrefixInput.value = subDomainPrefix || '';
            currencyInput.value = currencyName || '';
            currencyCodeInput.value = currencyCode || '';
            languageInput.value = languageName || '';
            languageCodeInput.value = languageCode || '';

            // Update the price field's symbol and placeholder
            priceInput.setAttribute('data-currency-symbol', currencySymbol || '$');  // Store the currency symbol
            priceInput.placeholder = `${currencySymbol || '$'} 99,999.00`;  // Set the placeholder with the symbol
            priceInput.value = priceInput.value.replace(currencySymbol, '').trim();  // Ensure no symbol in value
        });

        // Pre-fill the form if a country is selected by default (e.g., if $user is set)
        if (countrySelect.value) {
            const selectedOption = countrySelect.options[countrySelect.selectedIndex];
            const subDomainPrefix = selectedOption.getAttribute('data-sub-domain-prefix');
            const currencyName = selectedOption.getAttribute('data-currency-name');
            const currencyCode = selectedOption.getAttribute('data-currency-code');
            const currencySymbol = selectedOption.getAttribute('data-currency-symbol');
            const languageName = selectedOption.getAttribute('data-language-name');
            const languageCode = selectedOption.getAttribute('data-language-code');

            subDomainPrefixInput.value = subDomainPrefix || '';
            currencyInput.value = currencyName || '';
            currencyCodeInput.value = currencyCode || '';
            languageInput.value = languageName || '';
            languageCodeInput.value = languageCode || '';

            // Adjust price field placeholder and value
            priceInput.setAttribute('data-currency-symbol', currencySymbol || '$');
            priceInput.placeholder = `${currencySymbol || '$'} 99,999.00`;
            priceInput.value = priceInput.value.replace(currencySymbol, '').trim();  // Ensure no symbol in value
        }

        // Handle focus to remove the currency symbol only in the value (not in display)
        priceInput.addEventListener('focus', function () {
            const symbol = priceInput.getAttribute('data-currency-symbol');
            priceInput.value = priceInput.value.replace(symbol, '').trim();  // Remove the symbol from the value, keep it displayed
        });

        // Handle blur to add the currency symbol back to the placeholder if it's empty
        priceInput.addEventListener('blur', function () {
            const symbol = priceInput.getAttribute('data-currency-symbol');
            if (priceInput.value && !priceInput.value.includes(symbol)) {
                priceInput.value = `${symbol} ${priceInput.value}`;  // Keep symbol in the display (value)
            }
        });

        // Ensure the symbol is removed before form submission (to avoid validation errors)
        const form = priceInput.closest('form');  // Assuming your price input is inside a form
        form.addEventListener('submit', function (event) {
            const symbol = priceInput.getAttribute('data-currency-symbol');
            if (priceInput.value.includes(symbol)) {
                priceInput.value = priceInput.value.replace(symbol, '').trim();  // Remove symbol on submit
            }
        });
    });
</script>
@endpush
