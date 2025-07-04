<div class="container">
    <div class="row justify-content-center" style="display: flex;justify-content:center;align-items:center;height:100%;background-color:rgb(243 244 246);line-height:35px;">
        <div class="col-md-8" style="width:100%;">
            <div class="card">
                <h1 style="text-align:center;font-size: 3em;margin-bottom: 25px;">MISO</h1>
                <div class="card-body" style="border-radius: 0.5rem; display:block;margin:auto;padding: 1rem 1.5rem; max-width:28rem;background-color:#fff; box-shadow: var(0 4px 6px -1px rgb(0 0 0 / .1), 0 2px 4px -2px rgb(0 0 0 / .1));">
                    <div style="font-weight:600" class="card-header">{{ __('Утасны дугаар баталгаажуулах') }}</div>
                    @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{session('error')}}
                    </div>
                    @endif
                    @if (session('status'))
                    <div>
                        {{session('status')}}
                    </div>
                    @endif
                    <p>Энэ утасны дугаарлуу явуулсан нэг удаагийн кодийг оруулна уу: {{request('phone')}}</p>
                    <div style="position: relative; height:8rem">
                        <form action="{{route('verification.phone')}}" method="post">
                            @csrf
                            <div class="row" style="margin-top:0.5rem">
                                <label for="verification_code"
                                    class="col-md-4 col-form-label text-md-right">{{ __('Нэг удаагийн код') }}</label>
                                <div class="col-md-6" style="margin-top:0.25rem">
                                    <input type="hidden" name="phone" value="{{request('phone')}}">
                                    <input type="hidden" name="phone_prefix" value="{{request('phone_prefix')}}">
                                    <input id="verification_code" type="tel"
                                        class="form-control @error('verification_code') is-invalid @enderror"
                                        name="verification_code" value="{{ old('verification_code') }}" required>
                                    @error('verification_code')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mb-0">
                                <div class="col-md-6 offset-md-4" >
                                    <button type="submit" class="reset-button">
                                        {{ __('Баталгаажуулах') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                        <form style="position: absolute; right:0; bottom: -12;" method="POST" action="{{ route('phone.sendCodeAgain') }}">
                            @csrf
                            <input type="hidden" name="phone" value="{{request('phone')}}">
                            <input type="hidden" name="phone_prefix" value="{{request('phone_prefix')}}">
                            <div class="mb-3">
                                <button class="reset-button" type="submit">{{ __('Дахин код илгээх') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body{
        margin: 0;
    }
    .reset-button {
        margin-top: 1rem;
        display: block;
        text-align: center;
        color: #fff;
        background-color: #000;
        margin-bottom: 0px;
        padding: 0.5rem 1rem;
        text-transform: uppercase;
        font-weight: 600;
        font-size: 0.75rem;
        line-height: 1rem;
        float: left;
        cursor: pointer;
    }
    #verification_code {
        width: 100%;
        height: 20px;
        line-height: 20px;
        text-indent: 12px;
        padding: 15px 0px 15px 0px;
        font-size: 18px;
        border: 1px solid #b8b8b8;
        background: whitesmoke;
    }
    .card-body {
        display: block;
        width: 40%;
        margin: auto;
    }
    .row{
        zoom: 1;
        position: relative;
        box-sizing: border-box;
    }
    .container{
        display: block;
        width: 100%;
        height: 100%;
        margin: 0px 0px 0px 0px;
        background: #ddd;
        font-family: "Noto Sans", sans-serif !important;
        font-size: 17px;
    }
</style>