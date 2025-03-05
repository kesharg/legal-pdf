<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card custom-card">
            <div class="card-body">
                <h4 class="card-title">{{ localize("Price") }}</h4>
                <br />
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="price" :isRequired="false">
                                {{ localize("Price") }}
                            </x-form.label>
                            <x-form.input
                                type="text"
                                id="price"
                                name="price"
                                placeholder="99,999.00"
                                value="{{ isset($user) ? $user->total_price : old('total_price') }}"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
