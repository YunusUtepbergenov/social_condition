<aside id="layout-menu" class="sidebar">
    {{-- Brand / Logo --}}
    <div class="sidebar-brand">
        <a href="/" class="sidebar-brand-link">
            <img src="https://ijro.cerr.uz/assets/img/logo.svg" alt="Logo">
        </a>
    </div>

    {{-- Navigation --}}
    <div class="sidebar-nav">

        {{-- District analysis section --}}
        <div class="sidebar-section">
            <span class="sidebar-section-label">Туманлар кесимида</span>
            <a href="{{ route('mood') }}"
               class="sidebar-nav-item {{ Request::path() == 'mood' ? 'active' : '' }}">
                <span class="nav-icon"><i class="bx bx-smile"></i></span>
                <span>Истеъмолчилар кайфияти</span>
            </a>
            <a href="{{ route('protests') }}"
               class="sidebar-nav-item {{ Request::path() == 'protests' ? 'active' : '' }}">
                <span class="nav-icon"><i class="bx bx-error-circle"></i></span>
                <span>Оммавий норозиликлар</span>
            </a>
            <a href="{{ route('indicators') }}"
               class="sidebar-nav-item {{ Request::path() == 'indicators' ? 'active' : '' }}">
                <span class="nav-icon"><i class="bx bx-bar-chart-alt-2"></i></span>
                <span>Асосий кўрсаткичлар</span>
            </a>
            <a href="{{ route('clusters') }}"
               class="sidebar-nav-item {{ Request::path() == 'clusters' ? 'active' : '' }}">
                <span class="nav-icon"><i class="bx bx-git-branch"></i></span>
                <span>Ҳудудлар тоифалари</span>
            </a>
        </div>

        {{-- Regional analysis section --}}
        <div class="sidebar-section">
            <span class="sidebar-section-label">Вилоят кесимида</span>
            <a href="{{ route('sentiment.mood') }}"
               class="sidebar-nav-item {{ Request::path() == 'sentiment/mood' ? 'active' : '' }}">
                <span class="nav-icon"><i class="bx bx-user-voice"></i></span>
                <span>Аҳоли кайфияти</span>
            </a>
            <a href="{{ route('sentiment.survey') }}"
               class="sidebar-nav-item {{ Request::path() == 'sentiment/survey' ? 'active' : '' }}">
                <span class="nav-icon"><i class="bx bx-poll"></i></span>
                <span>Сўровнома натижалари</span>
            </a>
        </div>

        {{-- Logout --}}
        <div class="sidebar-section sidebar-section-bottom">
            <form action="{{ route('logout') }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" class="sidebar-nav-item sidebar-nav-item-btn sidebar-logout-item">
                    <span class="nav-icon"><i class="bx bx-log-out"></i></span>
                    <span>Чиқиш</span>
                </button>
            </form>
        </div>

    </div>

    {{-- User --}}
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <img src="{{ Auth::user()->avatar ? asset('user_image/'.Auth::user()->avatar) : asset('user_image/avatar.jpg') }}"
                 class="sidebar-user-avatar" alt="">
            <span class="sidebar-user-name">{{ Auth::user()->name }}</span>
        </div>
    </div>
</aside>
