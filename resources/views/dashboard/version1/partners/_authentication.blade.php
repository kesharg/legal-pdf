<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card custom-card">
            <div class="top-left"></div>
            <div class="top-right"></div>
            <div class="bottom-left"></div>
            <div class="bottom-right"></div>
            <div class="card-header justify-content-between">
                <div class="card-title">
                    <h4 class="card-title">{{ localize("Security") }}</h4>
                </div>
            </div>
            <div class="card-body">
                <div class="row">

                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="userName" :isRequired="true">{{ localize('User Name') }}</x-form.label>
                            <x-form.input type="text" id="userName" name="username" placeholder="Ex. John123"
                                value="{{ isset($user) ? $user->username : old('username') }}" />
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-group has-validation">
                            <x-form.label for="password" :isRequired="true">{{ localize('Password') }}</x-form.label>
                            <x-form.input type="password" id="password" name="password" placeholder="Ex. password"
                                value="{{ isset($user) ? $user->password : old('password') }}" />
                        </div>
                    </div>

                    @if (isset($user))
                        <div class="col-lg-4">
                            <div class="form-group has-validation">
                                <x-form.label for="is_active" :isRequired="true">{{ localize('Status') }}</x-form.label>

                                <x-form.select id="is_active" name="is_active" >
                                    <option value="1" @if ($user->is_active == 1) selected @endif>
                                        {{ localize('Active') }}</option>
                                    <option value="0" @if ($user->is_active == 0) selected @endif>
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
