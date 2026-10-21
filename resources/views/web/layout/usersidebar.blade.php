<div class="user-sidebar">
    <div class="user-info d-xl-flex gap-2 pb-4 mb-3">
        <div class="col-xl-3 col-12 mb-xl-0 mb-3">
            <div class="avatar-upload mx-auto d-flex justify-content-center">
                <div class="avatar-preview-two ">
                    <div id="imagepreview-two">
                        <img src="{{ helper::image_path(Auth::user()->profile_image) }}" alt="">
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <div class="col-12">
                <p
                    class="mb-0 text-center fw-600 {{ session()->get('direction') == '2' ? 'text-xl-end' : 'text-xl-start' }}">
                    {{ Auth::user()->name }}</p>
                <p class="mb-0 text-center {{ session()->get('direction') == '2' ? 'text-xl-end' : 'text-xl-start' }}">
                    {{ Auth::user()->email }}</p>
            </div>
        </div>
    </div>

    <li>
        <a class="{{ request()->is('profile') ? 'active' : '' }}" href="{{ route('user-profile') }}">
            <i class="mx-2 fa-regular fa-user"></i>{{ trans('labels.my_profile') }} </a>
    </li>
    <li>
        <a class="{{ request()->is('orders*') ? 'active' : '' }}" href="{{ route('order-history') }}">
            <i class="mx-2 fa fa-list-check"></i>{{ trans('labels.my_orders') }} </a>
    </li>
    <li>
        <a class="{{ request()->is('favouritelist') ? 'active' : '' }}" href="{{ route('user-favouritelist') }}">
            <i class="mx-2 fa-regular fa-heart"></i>{{ trans('labels.favourite_list') }} </a>
    </li>

    @if (helper::appdata()->pickup_delivery != 3)
        <li>
            <a class="{{ request()->is('address*') ? 'active' : '' }}" href="{{ route('address') }}">
                <i class="mx-2 fa-regular fa-map"></i>{{ trans('labels.my_addresses') }} </a>
        </li>
    @endif

   {{-- <li>
       <a class="{{ request()->is('refer-earn') ? 'active' : '' }}" href="{{ route('refer-earn') }}">
           <i class="mx-2 fa-solid fa-share-nodes"></i>{{ trans('labels.refer_earn') }} </a>
   </li> --}}

    <li>
        <a href="javascript:void(0)"
            onclick="logout('{{ route('logout') }}','{{ trans('messages.are_you_sure_logout') }}','{{ trans('labels.logout') }}')">
            <i class="mx-2 fa fa-arrow-right-from-bracket"></i>{{ trans('labels.logout') }} </a>
    </li>
</div>


<div class="profile-menu border rounded-3 my-3 d-block d-lg-none">
    <div class="accordion-item">
        <h2 class="accordion-header rounded-3">
            <button class="accordion-button rounded-3 bg-primary p-3 collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false"
                aria-controls="panelsStayOpen-collapseTwo">
                <i class="mx-2 fa-solid fa-bars text-white"></i>
                <p class="text-white mb-0">Navigation</p>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" style="">
            <div class="accordion-body px-4 py-3">
                <ul class="w-100 text-start">
                    <li class="mb-3 {{ request()->is('profile') ? 'active' : '' }}">
                        <a class="text-black" href="{{ route('user-profile') }}">
                            <i
                                class="fa-regular fa-user {{ session()->get('direction') == '2' ? 'ms-2' : 'me-2' }}"></i>{{ trans('labels.my_profile') }}
                        </a>
                    </li>
                    <li class="mb-3 {{ request()->is('orders*') ? 'active' : '' }}">
                        <a class="text-black" href="{{ route('order-history') }}">
                            <i
                                class="fa fa-list-check {{ session()->get('direction') == '2' ? 'ms-2' : 'me-2' }}"></i>{{ trans('labels.my_orders') }}
                        </a>
                    </li>
                    <li class="mb-3 {{ request()->is('favouritelist') ? 'active' : '' }}">
                        <a class="text-black" href="{{ route('user-favouritelist') }}">
                            <i
                                class="fa-regular fa-heart {{ session()->get('direction') == '2' ? 'ms-2' : 'me-2' }}"></i>{{ trans('labels.favourite_list') }}
                        </a>
                    </li>
                    <li class="mb-3 {{ request()->is('changepassword') ? 'active' : '' }}">
                        <a class="text-black" href="{{ route('user-changepassword') }}">
                            <i
                                class="fa fa-key {{ session()->get('direction') == '2' ? 'ms-2' : 'me-2' }}"></i>{{ trans('labels.change_password') }}
                        </a>
                    </li>

                    @if (helper::appdata()->pickup_delivery != 3)
                        <li class="mb-3 {{ request()->is('address*') ? 'active' : '' }}">
                            <a class="text-black" href="{{ route('address') }}">
                                <i
                                    class="fa-regular fa-map {{ session()->get('direction') == '2' ? 'ms-2' : 'me-2' }}"></i>{{ trans('labels.my_addresses') }}
                            </a>
                        </li>
                    @endif

                    {{--
                    <li class="mb-3 {{ request()->is('refer-earn') ? 'active' : '' }}">
                        <a class="text-black" href="{{ route('refer-earn') }}">
                            <i
                                class="fa-solid fa-share-nodes {{ session()->get('direction') == '2' ? 'ms-2' : 'me-2' }}"></i>{{ trans('labels.refer_earn') }}
                        </a>
                    </li> --}}
                    <li class="mb-3">
                        <a href="javascript:void(0)" class="text-black"
                            onclick="logout('{{ route('logout') }}','{{ trans('messages.are_you_sure_logout') }}','{{ trans('labels.logout') }}')">
                            <i
                                class="fa fa-arrow-right-from-bracket {{ session()->get('direction') == '2' ? 'ms-2' : 'me-2' }}"></i>{{ trans('labels.logout') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
