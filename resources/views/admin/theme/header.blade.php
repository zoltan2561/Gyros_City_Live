<header class="page-topbar">




    <div class="navbar-header">
        <div class="admin-sidebar-controls">
            <button type="button" id="admin-sidebar-toggle" class="admin-sidebar-toggle d-none d-lg-inline-flex"
                aria-controls="admin-sidebar" aria-expanded="true" aria-label="Oldalmenü elrejtése" title="Oldalmenü elrejtése">
                <i class="fa-regular fa-chevron-left" aria-hidden="true"></i><span>Menü</span>
            </button>
            <button class="navbar-toggler d-lg-none d-md-block px-md-4 px-3" type="button" data-bs-toggle="collapse"
                data-bs-target="#sidebarcollapse" aria-expanded="false" aria-controls="sidebarcollapse" aria-label="Menü megnyitása">
                <i class="fa-regular fa-bars fs-4"></i>
            </button>
        </div>
        <div class="px-md-3 px-0 d-flex align-items-center">

            @if (\App\Support\AdminPermissions::can(Auth::user(), 1))
                {{-- Étterem online/offline kapcsoló --}}
                @if (helper::check_restaurant_closed() == 1 )
                    @php $tooltiptitle = trans('messages.online_note'); @endphp
                    <input id="open-close-switch" type="checkbox" class="checkbox-switch" name="open-close"
                           value="1" checked
                           @if (env('Environment') == 'sendbox') onclick="myFunction()" disabled
                           @else onclick="changeStatus(2,'{{ URL::to('admin/change-status') }}')" @endif>
                @else
                    @php $tooltiptitle = trans('messages.offline_note'); @endphp
                    <input id="open-close-switch" type="checkbox" class="checkbox-switch" name="open-close"
                           value=""
                           @if (env('Environment') == 'sendbox') onclick="myFunction()" disabled
                           @else onclick="changeStatus(1,'{{ URL::to('admin/change-status') }}')" @endif>
                @endif
                <label for="open-close-switch" class="switch me-3" data-bs-toggle="tooltip" title="{{ $tooltiptitle }}">
        <span class="{{ session()->get('direction') == 2 ? 'switch__circle-rtl' : 'switch__circle' }}">
            <span class="switch__circle-inner"></span>
        </span>
                    <span class="switch__left {{ session()->get('direction') == 2 ? 'pe-2' : 'ps-2' }}">{{ trans('labels.off') }}</span>
                    <span class="switch__right {{ session()->get('direction') == 2 ? 'ps-2' : 'pe-2' }}">{{ trans('labels.on') }}</span>
                </label>

                {{-- ÚJ: Kiszállítás BE/KI kapcsoló --}}
                <form method="POST" action="{{ route('admin.toggleDelivery') }}" class="d-inline">
                    @csrf
                    @php $deliveryOn = (int)\App\Helpers\helper::app_setting('delivery_enabled', 1) === 1; @endphp
                    <button type="submit" class="btn btn-sm {{ $deliveryOn ? 'btn-warning' : 'btn-success' }}">
                        {{ $deliveryOn ? '🚚 Kiszállítás kikapcsolása' : '🚚 Kiszállítás bekapcsolása' }}
                    </button>
                </form>
            @endif

            @if (@helper::checkaddons('language'))
                <div class="position-relative mx-1">
                    <div class="dropdown d-lg-block d-none">
                        <a class="btn btn-sm border-primary dropdown-toggle" href="javascript:void(0)" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="{{ helper::image_path(session()->get('flag')) }}" alt=""
                                class="mx-1 rounded-5 language-width">
                            <span
                                class="{{ Session::get('theme') == 'dark' ? 'text-white' : '' }}">{{ session()->get('language') }}
                            </span>
                        </a>
                        <ul
                            class="dropdown-menu drop-menu {{ session()->get('direction') == 2 ? 'drop-menu-rtl' : 'drop-menu' }}">
                            @foreach (helper::language() as $lang)
                                <li>
                                    <a class="dropdown-item d-flex text-start d-flex"
                                        href="{{ URL::to('/language-' . $lang->code) }}">
                                        <img src="{{ helper::image_path($lang->image) }}" alt=""
                                            class="img-fluid mx-1 rounded-5 language-width">
                                        {{ $lang->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <!-- language-btn -->
                    <div class="dropdown d-block d-lg-none">
                        <a class="btn text-dark border dropdown-toggle px-3 py-1 fs-6" type="button"
                            id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-globe fs-5"></i></a>
                        <ul class="dropdown-menu {{ session()->get('direction') == '2' ? 'min-dropdown-rtl' : 'min-dropdown' }}"
                            aria-labelledby="dropdownMenuButton1">
                            @foreach (helper::language() as $lang)
                                <li>
                                    <a class="dropdown-item text-dark d-flex"
                                        href="{{ URL::to('/language-' . $lang->code) }}">
                                        <img src="{{ helper::image_path($lang->image) }}"
                                            class="img-fluid lag-img mx-1 rounded-5 language-width"
                                            alt="">{{ $lang->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <!-- language-btn -->
                </div>
            @endif
            <div class="dropwdown d-inline-block">
                <button class="btn header-item" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="{{ helper::image_path(Auth::user()->profile_image) }}">
                    <span class="d-none d-xxl-inline-block d-xl-inline-block ms-1">{{ Auth::user()->name }}</span>
                    <i class="fa-regular fa-angle-down d-none d-xxl-inline-block d-xl-inline-block"></i>
                </button>
                <div class="dropdown-menu box-shadow">
                    @if (Auth::user()->type != 1)
                        @if (in_array('22', explode(',', helper::get_roles())))
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ URL::to('admin/settings#edit_profile') }}">
                                <i class="fa-regular fa-user mx-2"></i>{{ trans('labels.edit_profile') }} </a>
                            <a class="dropdown-item d-flex align-items-center"
                                href="{{ URL::to('admin/settings#change_password') }}">
                                <i class="fa-regular fa-key mx-2"></i>{{ trans('labels.change_password') }} </a>
                        @endif
                    @else
                        <a class="dropdown-item d-flex align-items-center"
                            href="{{ URL::to('admin/settings#edit_profile') }}">
                            <i class="fa-regular fa-user mx-2"></i>{{ trans('labels.edit_profile') }} </a>
                        <a class="dropdown-item d-flex align-items-center"
                            href="{{ URL::to('admin/settings#change_password') }}">
                            <i class="fa-regular fa-key mx-2"></i>{{ trans('labels.change_password') }} </a>
                    @endif
                    <a class="dropdown-item d-flex align-items-center cursor-pointer"
                        onclick="logout('{{ URL::to('/admin/logout') }}')">
                        <i class="fa-regular fa-arrow-right-from-bracket mx-2"></i>{{ trans('labels.logout') }} </a>
                </div>
            </div>
        </div>
    </div>
</header>
