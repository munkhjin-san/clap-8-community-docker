<div class="container">
    <div  style="display: flex;justify-content:center;align-items:center;height:100%;background-color:rgb(243 244 246);line-height:35px;">
        <div class="card">
            <h1 style="text-align:center;font-size: 3em;margin-bottom: 25px;">CLAP</h1>
            <div class="card-body" style="border-radius: 0.5rem; display:block;margin:auto;padding: 1rem 1.5rem; max-width:28rem;background-color:#fff; box-shadow: var(0 4px 6px -1px rgb(0 0 0 / .1), 0 2px 4px -2px rgb(0 0 0 / .1));">
                <div class="card-header" style="font-weight: 600">{{ __('Мэйл хаягаа баталгаажуулна уу') }}</div>
                @if (session('resent'))
                    <div style="color:#0f5132;font-weight: 600" class="alert alert-success" role="alert">
                        {{ __('Шинэ баталгаажуулах мэйл илгээгдсэн.') }}
                    </div>
                @endif
                {{ __('Цааш үргэлжлүүлэхийн тулд, баталгаажуулах мэйл ирсэн үгүйг шалгана уу.') }}
                {{ __('Хэрэв мэйл очоогүй бол') }},
                <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary reset-button">{{ __('энд дарна уу') }}</button>.
                </form>
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
    .card-body {
        display: block;
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
