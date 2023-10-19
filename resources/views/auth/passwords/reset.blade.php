@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center" style="display: flex;justify-content:center;align-items:center;height:100vh;background-color:rgb(243 244 246)">
        <div class="col-md-8" style="width:100%;">
            <div class="card">
                <h1 style="text-align:center;font-size: 3em;margin-bottom: 25px;">CLAP</h1>

                <div class="card-body" style="border-radius: 0.5rem; display:block;margin:auto;height:100%;padding: 1rem 1.5rem; max-width:28rem;background-color:#fff; box-shadow: var(0 4px 6px -1px rgb(0 0 0 / .1), 0 2px 4px -2px rgb(0 0 0 / .1));">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Мэйл хаяг') }}</label>

                            <div class="col-md-6" style="margin-top:0.25rem" >
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Шинэ нууц үг') }}</label>

                            <div class="col-md-6" style="margin-top:0.25rem">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Шинэ нууц үг баталгаажуулах') }}</label>

                            <div class="col-md-6" style="margin-top:0.25rem" >
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary reset-button">
                                    {{ __('Нууц үг шинэчлэх') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
