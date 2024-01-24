<html lang="en"><head>
    <title>Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="https://cerr.uz/themes/cer/icon/favicon.ico">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <body>
        <section class="ftco-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-7 col-lg-5">
                        <div class="login-wrap p-4 p-md-5">
                            <div class="icon d-flex align-items-center justify-content-center">
                                <img src="{{ asset('img/213232-0cd1efa818.jpg')}}">
                            </div>
                            <h3 class="text-center mb-4">Авторизация</h3>
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('login') }}" class="login-form">
                                @csrf
                                <div class="form-group">
                                    <input type="email" name="email" class="form-control rounded-left" placeholder="Логин" value="{{ old('email') }}" required="">
                                </div>
                                <div class="form-group d-flex">
                                    <input type="password" class="form-control rounded-left" placeholder="Пароль" name="password" required autocomplete="current-password">
                                </div>
                                <div class="form-group">
                                    <a href="{{ route('password.request') }}" style="float: right; margin-bottom:5px; font-size:14px; color:#317ba2;">Пароль ёдингиздан чиқдими?</a>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="form-control btn btn-primary rounded submit px-3">Кириш</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
    </body>
</html>
