<div>
    <nav class="layout-navbar container-fluid navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
          <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
          </a>
        </div>
        <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
          <!-- Search -->
          <div class="navbar-nav align-items-center">
            <div class="nav-item d-flex align-items-center">
              <div class="form-row align-items-center my-3 mx-3">
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" wire:model="radio" wire:click="radioChanged('mood')" id="gridRadios1" value="mood" selected>
                  <label class="form-check-label filter-texts" for="gridRadios1">
                  Аҳоли кайфияти
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" wire:model="radio" wire:click="radioChanged('protests')" id="gridRadios2" value="protests">
                  <label class="form-check-label filter-texts" for="gridRadios2">
                  Оммавий норозиликлар
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" wire:model="radio" wire:click="radioChanged('indicator')" id="gridRadios3" value="indicator">
                  <label class="form-check-label filter-texts" for="gridRadios3">
                  Асосий кўрсаткичлар
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" wire:model="radio" wire:click="radioChanged('clusters')" id="gridRadios4" value="clusters">
                  <label class="form-check-label filter-texts" for="gridRadios4">
                      Ҳудудлар кластери
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <select class="form-select multiline-select" wire:model="region">
                    <option value="republic">Республика бўйича</option>
                    <option value="1703">Андижон вилояти</option>
                    <option value="1706">Бухоро вилояти</option>
                    <option value="1708">Жиззах вилояти</option>
                    <option value="1735">Қорақалроғистон Республикаси</option>
                    <option value="1710">Қашқадарё вилояти</option>
                    <option value="1712">Навоий вилояти</option>
                    <option value="1714">Наманган вилояти</option>
                    <option value="1718">Самарқанд вилояти</option>
                    <option value="1722">Сурхандарё вилояти</option>
                    <option value="1724">Сирдарё вилояти</option>
                    <option value="1726">Тошкент шахри</option>
                    <option value="1727">Тошкент вилояти</option>
                    <option value="1730">Фарғона вилояти</option>
                    <option value="1733">Хоразм вилояти</option>
                  </select>
                </div>
                @if ($radio == 'indicator')
                  <div class="form-check form-check-inline" wire:ignore>
                    <select class="form-select multiline-select" id="select-test" wire:model="indicator">
                      @foreach ($indicators as $indicator)
                        <option value="{{$indicator}}" data-value="{{$indicator}}">{{$translates[$indicator]}}</option>
                      @endforeach
                    </select>
                  </div>
                @endif
              </div>
            </div>
          </div>
          <!-- /Search -->
          <ul class="navbar-nav flex-row align-items-center ms-auto">
            <li class="nav-item lh-1 me-3">
              <span></span>
            </li>
            <li class="nav-item dropdown has-arrow main-drop">
              <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                <span class="user-img"><img src="{{ (Auth::user()->avatar) ? asset('user_image/'.Auth::user()->avatar) : asset('user_image/avatar.jpg') }}" class="user_image" alt="">
                <span class="status online"></span></span>
                <span>{{ Auth::user()->name }}</span>
              </a>
              <div class="dropdown-menu">
                <a class="dropdown-item" href="{{route('task.download')}}">Топшириқ</a>
                <form action="{{ route('logout') }}" method="POST">
                  @csrf
                  <button class="dropdown-item">Чиқиш</button>
                </form>
              </div>
            </li>
            <!--/ User -->
          </ul>
        </div>
    </nav>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:load', function (event) {
          Livewire.on('updateSelecttwo', () => {
            $("#select-test").select2();
          });

          $(document).on('change', '#select-test', function (e) {
                @this.set('indicator', $(this).find(':selected').data('value'));
          });
        });
    </script>
@endpush

<script>
  window.addEventListener("DOMContentLoaded", function () {
      Livewire.emit('child-mounted');
  });
</script>