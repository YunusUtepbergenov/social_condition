<div>
  <div wire:loading>
      <div class="loading"></div>
  </div>
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" style="height:99vh;;width:16.25rem;background:#34444c">
      <div class="app-brand demo">
        <a href="#" class="app-brand-link">
          <img src="https://ijro.cerr.uz/assets/img/logo.svg" width="100" height="40" alt="" style="width:100%; margin:auto">
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
          <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
      </div>

        <ul class="menu-inner py-2 ps ps--active-y">
          {{-- <li class="menu-item">
            <a href="#" class="menu-link">
              <i class="menu-icon tf-icons bx bx-home-circle"></i>
              <div data-i18n="Analytics">Бош саҳифа</div>
            </a>
          </li> --}}
          <li class="menu-item {{ Request::path() == '/' ? 'active' : '' }}">
            <a href="/" class="menu-link">
              <i class="menu-icon tf-icons bx bxs-coin-stack"></i>
              <div data-i18n="Analytics">Туманлар кесимида таҳлиллар</div>
            </a>
          </li>
          <li class="menu-item {{ Request::path() == 'sentiment' ? 'active' : '' }}">
            <a href="{{ route('sentiment') }}" class="menu-link">
              <i class="menu-icon tf-icons bx bxs-coin-stack"></i>
              <div data-i18n="Analytics">Вилоят кесимида таҳлиллар</div>
            </a>
          </li>
          {{-- <li class="menu-item">
            <a href="#" class="menu-link">
              <i class='menu-icon tf-icons bx bxs-cog'></i>
              <div data-i18n="Support">Созламалар</div>
            </a>
          </li> --}}
        </ul>

    </aside>
</div>
