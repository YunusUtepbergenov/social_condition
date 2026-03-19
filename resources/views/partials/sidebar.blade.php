<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" style="height:99vh;width:16.25rem;background:#34444c">
    <div class="app-brand demo">
        <a href="/" class="app-brand-link">
            <img src="https://ijro.cerr.uz/assets/img/logo.svg" width="100" height="40" alt="" style="width:100%; margin:auto">
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="sidebar-content">
        <ul class="menu-inner py-2 ps ps--active-y" style="margin-bottom:0;padding-bottom:0 !important">
            {{-- District-level analysis submenu --}}
            <li class="menu-item {{ in_array(Request::path(), ['mood', 'protests', 'indicators', 'clusters']) ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bxs-coin-stack"></i>
                    <div data-i18n="Analytics">Туманлар кесимида таҳлиллар</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ Request::path() == 'mood' ? 'active' : '' }}">
                        <a href="{{ route('mood') }}" class="menu-link">
                            <div>Истеъмолчилар кайфияти</div>
                        </a>
                    </li>
                    <li class="menu-item {{ Request::path() == 'protests' ? 'active' : '' }}">
                        <a href="{{ route('protests') }}" class="menu-link">
                            <div>Оммавий норозиликлар</div>
                        </a>
                    </li>
                    <li class="menu-item {{ Request::path() == 'indicators' ? 'active' : '' }}">
                        <a href="{{ route('indicators') }}" class="menu-link">
                            <div>Асосий кўрсаткичлар</div>
                        </a>
                    </li>
                    <li class="menu-item {{ Request::path() == 'clusters' ? 'active' : '' }}">
                        <a href="{{ route('clusters') }}" class="menu-link">
                            <div>Ҳудудлар тоифалари</div>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Regional-level analysis submenu --}}
            <li class="menu-item {{ str_starts_with(Request::path(), 'sentiment') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bxs-coin-stack"></i>
                    <div data-i18n="Analytics">Вилоят кесимида таҳлиллар</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ Request::path() == 'sentiment/mood' ? 'active' : '' }}">
                        <a href="{{ route('sentiment.mood') }}" class="menu-link">
                            <div>Аҳоли кайфияти</div>
                        </a>
                    </li>
                    <li class="menu-item {{ Request::path() == 'sentiment/survey' ? 'active' : '' }}">
                        <a href="{{ route('sentiment.survey') }}" class="menu-link">
                            <div>Сўровнома натижалари</div>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <img src="{{ (Auth::user()->avatar) ? asset('user_image/'.Auth::user()->avatar) : asset('user_image/avatar.jpg') }}" class="sidebar-user-avatar" alt="">
            <span class="sidebar-user-name">{{ Auth::user()->name }}</span>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="sidebar-logout-btn" type="submit">
                <i class="bx bx-log-out"></i> Чиқиш
            </button>
        </form>
    </div>
</aside>
