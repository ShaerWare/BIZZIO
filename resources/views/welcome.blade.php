<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width, height=device-height" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Bizzo.ru') }} – соединяя бизнес</title>

    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/dashicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/css.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-bar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bbpress.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/buddypress.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/unicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/beehive.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/elementor.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">  

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,600,700,300italic,400italic,600italic,700italic|Quicksand:700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" sizes="32x32" />
</head>

<body class="home-page bp-nouveau home wp-singular full-width">

    <!-- Preloader -->
    <div class="beehive-preloader preloader-1">
        <span></span>
    </div>

    <div id="beehive-page" class="site">

        <!-- Header -->
        <header id="masthead" class="site-header default-header overlay-header menu-color-white user-nav-active">
            <nav class="navbar beehive-navbar default fixed-top">
                <div class="container">
                    <!-- Logo -->
                    <a class="navbar-brand" href="{{ url('/') }}">
                        <img src="{{ asset('images/white-logo.svg') }}" title="{{ config('app.name') }}" alt="{{ config('app.name') }}" class="default-logo" />
                    </a>

                    <!-- Mobile Toggle -->
                    <button class="beehive-toggler navbar-icon" type="button">
                        <span class="icon-bar bar1"></span>
                        <span class="icon-bar bar2"></span>
                        <span class="icon-bar bar3"></span>
                    </button>

                    <!-- Navigation Menu -->
                    <div class="navbar-main-container">
                        <div class="menu-label">
                            <span class="h5">Main Menu</span>
                        </div>
                        <ul class="navbar-nav navbar-main">
                            <li class="menu-item active">
                                <a href="{{ url('/') }}" class="nav-link">Home</a>
                            </li>
                            <li class="menu-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">Network</a>
                                <ul class="dropdown-menu">
                                    <li><a href="{{ url('/forums') }}" class="dropdown-item">Forums</a></li>
                                    <li><a href="{{ url('/groups') }}" class="dropdown-item">Groups</a></li>
                                    <li><a href="{{ route('companies.index') }}" class="dropdown-item">Компании</a></li>
                                    <li><a href="{{ route('projects.index') }}" class="dropdown-item">Проекты</a></li>
                                    <li><a href="{{ url('/members') }}" class="dropdown-item">Members</a></li>
                                </ul>
                            </li>
                            <li class="menu-item">
                                <a href="{{ url('/blog') }}" class="nav-link">Blog</a>
                            </li>
                            <li class="menu-item">
                                <a href="{{ url('/contact') }}" class="nav-link">Contact</a>
                            </li>
                        </ul>
                    </div>

                    <!-- User Navigation -->
                    <ul class="navbar-nav navbar-user">
                        @guest
                            <li class="nav-item">
                                <a href="#" class="nav-link login" data-toggle="modal" data-target="#login-modal">Login</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('register') }}" class="nav-link register">Register</a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                                    <img src="{{ Auth::user()->avatar ?? asset('images/default-avatar.png') }}" alt="{{ Auth::user()->name }}" class="avatar">
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a href="{{ route('dashboard') }}" class="dropdown-item">Dashboard</a></li>
                                    <li><a href="{{ route('profile.edit') }}" class="dropdown-item">Profile</a></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Logout</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <div id="content" class="site-content">
            <div id="primary" class="content-area">
                <div class="layout full">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-12 col-main">
                                <main id="main" class="main-content">

                                    <!-- Hero Section -->
                                    <section class="hero-section" style="background: url('{{ asset('videos/8.mp4') }}') no-repeat center center; background-size: cover; min-height: 100vh; position: relative;">
                                        <!-- Video Background (опционально) -->
                                        <div class="video-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden;">
                                            <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
                                                <source src="{{ asset('videos/8.mp4') }}" type="video/mp4">
                                            </video>
                                        </div>

                                        <!-- Overlay -->
                                        <div class="overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);"></div>

                                        <!-- Content -->
                                        <div class="container" style="position: relative; z-index: 10; padding-top: 150px;">
                                            <div class="row">
                                                <!-- Left Column: Features -->
                                                <div class="col-md-6 background-primary-09" style="padding: 40px; background: rgba(42, 91, 221, 0.9); border-radius: 10px; color: white;">
                                                    <h1 style="font-size: 2.5rem; font-weight: 700; margin-bottom: 30px;">
                                                        Бизнес-сеть для поиска партнеров<br />и реализации проектов
                                                    </h1>

                                                    <!-- Feature 1: Компании -->
                                                    <div class="feature-box" style="display: flex; align-items: center; margin-bottom: 20px;">
                                                        <div class="icon" style="width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                                            <svg style="width: 30px; height: 30px; fill: #2A5BDD;" viewBox="0 0 640 512">
                                                                <path d="M192 256c61.9 0 112-50.1 112-112S253.9 32 192 32 80 82.1 80 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C51.6 288 0 339.6 0 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zM480 256c53 0 96-43 96-96s-43-96-96-96-96 43-96 96 43 96 96 96zm48 32h-3.8c-13.9 4.8-28.6 8-44.2 8s-30.3-3.2-44.2-8H432c-20.4 0-39.2 5.9-55.7 15.4 24.4 26.3 39.7 61.2 39.7 99.8v38.4c0 2.2-.5 4.3-.6 6.4H592c26.5 0 48-21.5 48-48 0-61.9-50.1-112-112-112z"/>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h4 style="margin: 0; font-weight: normal;">Компании</h4>
                                                            <p style="margin: 0; opacity: 0.8;">Поиск партнеров</p>
                                                        </div>
                                                    </div>

                                                    <!-- Feature 2: Проекты -->
                                                    <div class="feature-box" style="display: flex; align-items: center; margin-bottom: 20px;">
                                                        <div class="icon" style="width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                                            <svg style="width: 30px; height: 30px; fill: #2A5BDD;" viewBox="0 0 512 512">
                                                                <path d="M320 336c0 8.84-7.16 16-16 16h-96c-8.84 0-16-7.16-16-16v-48H0v144c0 25.6 22.4 48 48 48h416c25.6 0 48-22.4 48-48V288H320v48zm144-208h-80V80c0-25.6-22.4-48-48-48H176c-25.6 0-48 22.4-48 48v48H48c-25.6 0-48 22.4-48 48v80h512v-80c0-25.6-22.4-48-48-48zm-144 0H192V96h128v32z"/>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h4 style="margin: 0; font-weight: normal;">Проекты</h4>
                                                            <p style="margin: 0; opacity: 0.8;">Совместные проекты</p>
                                                        </div>
                                                    </div>

                                                    <!-- Feature 3: Тендеры -->
                                                    <div class="feature-box" style="display: flex; align-items: center;">
                                                        <div class="icon" style="width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                                                            <svg style="width: 30px; height: 30px; fill: #2A5BDD;" viewBox="0 0 288 512">
                                                                <path d="M209.2 233.4l-108-31.6C88.7 198.2 80 186.5 80 173.5c0-16.3 13.2-29.5 29.5-29.5h66.3c12.2 0 24.2 3.7 34.2 10.5 6.1 4.1 14.3 3.1 19.5-2l34.8-34c7.1-6.9 6.1-18.4-1.8-24.5C238 74.8 207.4 64.1 176 64V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48h-2.5C45.8 64-5.4 118.7.5 183.6c4.2 46.1 39.4 83.6 83.8 96.6l102.5 30c12.5 3.7 21.2 15.3 21.2 28.3 0 16.3-13.2 29.5-29.5 29.5h-66.3C100 368 88 364.3 78 357.5c-6.1-4.1-14.3-3.1-19.5 2l-34.8 34c-7.1 6.9-6.1 18.4 1.8 24.5 24.5 19.2 55.1 29.9 86.5 30v48c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-48.2c46.6-.9 90.3-28.6 105.7-72.7 21.5-61.6-14.6-124.8-72.5-141.7z"/>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h4 style="margin: 0; font-weight: normal;">Тендеры</h4>
                                                            <p style="margin: 0; opacity: 0.8;">Проведение торгов</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Right Column: Login Form -->
                                                <div class="col-md-6" style="padding: 40px; background: white; border-radius: 10px;">
                                                    <div style="text-align: center; margin-bottom: 30px;">
                                                        <img src="{{ asset('images/assembly-icon.png') }}" alt="Icon" style="width: 57px; height: 57px; margin-bottom: 15px;">
                                                        <p style="font-size: 1.2rem; font-weight: 600; color: #333;">Присоединяйтесь к бизнес-сообществу</p>
                                                    </div>

                                                    <!-- Login Form -->
                                                    <form action="{{ route('login') }}" method="POST">
                                                        @csrf
                                                        <div class="form-group" style="margin-bottom: 20px;">
                                                            <div class="input-wrapper" style="position: relative;">
                                                                <span class="icon" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999;">
                                                                    <i class="uil-user"></i>
                                                                </span>
                                                                <input type="text" name="email" required placeholder="Email or username" style="width: 100%; padding: 12px 12px 12px 45px; border: 1px solid #ddd; border-radius: 5px;">
                                                            </div>
                                                        </div>

                                                        <div class="form-group" style="margin-bottom: 20px;">
                                                            <div class="input-wrapper" style="position: relative;">
                                                                <span class="icon" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999;">
                                                                    <i class="uil-key-skeleton-alt"></i>
                                                                </span>
                                                                <input type="password" name="password" required placeholder="Password" style="width: 100%; padding: 12px 12px 12px 45px; border: 1px solid #ddd; border-radius: 5px;">
                                                            </div>
                                                        </div>

                                                        <div class="form-options" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                                                            <label>
                                                                <input type="checkbox" name="remember"> Remember
                                                            </label>
                                                            <a href="{{ route('password.request') }}" style="color: #2A5BDD;">Lost Password?</a>
                                                        </div>

                                                        @if ($errors->any())
                                                            <div class="alert alert-danger" style="background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                                                                {{ $errors->first() }}
                                                            </div>
                                                        @endif

                                                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; background: #28A745; border: none; border-radius: 5px; color: white; font-size: 1rem; font-weight: 600; cursor: pointer;">
                                                            Войти
                                                        </button>

                                                        <div class="register-link" style="text-align: center; margin-top: 20px;">
                                                            <a href="{{ route('register') }}" style="color: #2A5BDD; font-weight: 600;">Create an account</a>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Footer -->
                                    <footer style="text-align: center; padding: 20px; background: rgba(0,0,0,0.8); color: white; margin-top: 50px;">
                                        <p>ООО "АРИС" | 2025</p>
                                    </footer>

                                </main>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Login Modal (опционально, если нужен модальный вход) -->
    <div class="modal fade login-modal" id="login-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Log into your account</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <i class="icon ion-close-round"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        <!-- Форма аналогична выше -->
                        <!-- ... -->
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/popper.min.js') }}"></script>
    <script src="{{ asset('js/beehive.min.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
</body>
</html>