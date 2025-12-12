<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Bizzo.ru') }} – соединяя бизнес</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&family=Quicksand:wght@700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Grid Only (минимальная версия) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap-grid.min.css" rel="stylesheet">
    
    <!-- Unicons (для иконок) -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" sizes="32x32" />
</head>

<body>

    <div id="beehive-page">

        <!-- Header -->
        <header class="site-header">
            <nav class="navbar">
                <!-- Logo -->
                <a href="{{ url('/') }}" class="navbar-brand">
                    <img src="{{ asset('images/white-logo.svg') }}" alt="{{ config('app.name') }}">
                </a>

                <!-- Mobile Toggle -->
                <button class="beehive-toggler" type="button" aria-label="Toggle navigation">
                    <span class="icon-bar bar1"></span>
                    <span class="icon-bar bar2"></span>
                    <span class="icon-bar bar3"></span>
                </button>

                <!-- Navigation -->
                <div class="navbar-main-container">
                    <ul class="navbar-nav navbar-main">
                        <li><a href="{{ url('/') }}" class="nav-link">Home</a></li>
                        <li><a href="{{ route('companies.index') }}" class="nav-link">Компании</a></li>
                        <li><a href="{{ route('projects.index') }}" class="nav-link">Проекты</a></li>
                        <li><a href="{{ url('/blog') }}" class="nav-link">Blog</a></li>
                        <li><a href="{{ url('/contact') }}" class="nav-link">Contact</a></li>
                    </ul>
                </div>

                <!-- User Navigation -->
                <ul class="navbar-nav navbar-user">
                    @guest
                        <li><a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#login-modal">Login</a></li>
                        <li><a href="{{ route('register') }}" class="nav-link">Register</a></li>
                    @else
                        <li>
                            <a href="{{ route('dashboard') }}" class="nav-link">
                                {{ Auth::user()->name }}
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="nav-link" style="background: none; border: none; cursor: pointer;">Logout</button>
                            </form>
                        </li>
                    @endguest
                </ul>
            </nav>
        </header>

        <!-- Main Content -->
        <div class="site-content">
            <main>

                <!-- Hero Section -->
                <section class="hero-section">
                    <!-- Video Background -->
                    <div class="video-container">
                        <video autoplay muted loop playsinline>
                            <source src="{{ asset('videos/8.mp4') }}" type="video/mp4">
                        </video>
                    </div>

                    <!-- Overlay -->
                    <div class="overlay"></div>

                    <!-- Content -->
                    <div class="hero-content">
                        <div class="row g-4">
                            
                            <!-- Left Column: Features -->
                            <div class="col-lg-6">
                                <div class="features-block">
                                    <h1>Бизнес-сеть для поиска партнеров<br>и реализации проектов</h1>

                                    <!-- Feature 1 -->
                                    <div class="feature-box">
                                        <div class="feature-icon">
                                            <svg viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M192 256c61.9 0 112-50.1 112-112S253.9 32 192 32 80 82.1 80 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C51.6 288 0 339.6 0 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zM480 256c53 0 96-43 96-96s-43-96-96-96-96 43-96 96 43 96 96 96zm48 32h-3.8c-13.9 4.8-28.6 8-44.2 8s-30.3-3.2-44.2-8H432c-20.4 0-39.2 5.9-55.7 15.4 24.4 26.3 39.7 61.2 39.7 99.8v38.4c0 2.2-.5 4.3-.6 6.4H592c26.5 0 48-21.5 48-48 0-61.9-50.1-112-112-112z"/>
                                            </svg>
                                        </div>
                                        <div class="feature-info">
                                            <h4>Компании</h4>
                                            <p>Поиск партнеров</p>
                                        </div>
                                    </div>

                                    <!-- Feature 2 -->
                                    <div class="feature-box">
                                        <div class="feature-icon">
                                            <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M320 336c0 8.84-7.16 16-16 16h-96c-8.84 0-16-7.16-16-16v-48H0v144c0 25.6 22.4 48 48 48h416c25.6 0 48-22.4 48-48V288H320v48zm144-208h-80V80c0-25.6-22.4-48-48-48H176c-25.6 0-48 22.4-48 48v48H48c-25.6 0-48 22.4-48 48v80h512v-80c0-25.6-22.4-48-48-48zm-144 0H192V96h128v32z"/>
                                            </svg>
                                        </div>
                                        <div class="feature-info">
                                            <h4>Проекты</h4>
                                            <p>Совместные проекты</p>
                                        </div>
                                    </div>

                                    <!-- Feature 3 -->
                                    <div class="feature-box">
                                        <div class="feature-icon">
                                            <svg viewBox="0 0 288 512" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M209.2 233.4l-108-31.6C88.7 198.2 80 186.5 80 173.5c0-16.3 13.2-29.5 29.5-29.5h66.3c12.2 0 24.2 3.7 34.2 10.5 6.1 4.1 14.3 3.1 19.5-2l34.8-34c7.1-6.9 6.1-18.4-1.8-24.5C238 74.8 207.4 64.1 176 64V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48h-2.5C45.8 64-5.4 118.7.5 183.6c4.2 46.1 39.4 83.6 83.8 96.6l102.5 30c12.5 3.7 21.2 15.3 21.2 28.3 0 16.3-13.2 29.5-29.5 29.5h-66.3C100 368 88 364.3 78 357.5c-6.1-4.1-14.3-3.1-19.5 2l-34.8 34c-7.1 6.9-6.1 18.4 1.8 24.5 24.5 19.2 55.1 29.9 86.5 30v48c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-48.2c46.6-.9 90.3-28.6 105.7-72.7 21.5-61.6-14.6-124.8-72.5-141.7z"/>
                                            </svg>
                                        </div>
                                        <div class="feature-info">
                                            <h4>Тендеры</h4>
                                            <p>Проведение торгов</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Login Form -->
                            <div class="col-lg-6">
                                <div class="login-block">
                                    <div class="login-header">
                                        <img src="{{ asset('images/assembly-icon.png') }}" alt="Icon">
                                        <p>Присоединяйтесь к бизнес-сообществу</p>
                                    </div>

                                    <!-- Login Form -->
                                    <form action="{{ route('login') }}" method="POST">
                                        @csrf
                                        
                                        <div class="form-group">
                                            <div class="input-wrapper">
                                                <span class="icon">
                                                    <i class="uil-user"></i>
                                                </span>
                                                <input type="text" name="email" class="form-control" required placeholder="Email or username">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="input-wrapper">
                                                <span class="icon">
                                                    <i class="uil-lock-alt"></i>
                                                </span>
                                                <input type="password" name="password" class="form-control" required placeholder="Password">
                                            </div>
                                        </div>

                                        <div class="form-options">
                                            <label>
                                                <input type="checkbox" name="remember"> Remember
                                            </label>
                                            <a href="{{ route('password.request') }}">Lost Password?</a>
                                        </div>

                                        @if ($errors->any())
                                            <div style="background: #ffe6e6; padding: 12px; border-radius: 8px; margin-bottom: 20px; color: #d32f2f; font-size: 0.9rem;">
                                                {{ $errors->first() }}
                                            </div>
                                        @endif

                                        <button type="submit" class="btn-login">Войти</button>

                                        <div class="register-link">
                                            <a href="{{ route('register') }}">Create an account</a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <footer class="site-footer">
                        <p>ООО "АРИС" | 2025</p>
                    </footer>
                </section>

            </main>
        </div>

    </div>

    <!-- Custom JS -->
    <script src="{{ asset('js/custom.js') }}"></script>
</body>
</html>