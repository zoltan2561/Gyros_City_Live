<!-- For Large Devices -->
<nav class="sidebar sidebar-lg">
    <div class="d-flex justify-content-center align-items-center mb-3 border-bottom border-white">
        <div class="navbar-header-logo pb-2">
            <a href="{{ url(\App\Support\AdminPermissions::landing(Auth::user())) }}" class="text-white fs-4">
                @if (Auth::user()->type == 1)
                    {{ trans('labels.admin_title') }}
                @elseif(Auth::user()->type == 4)
                    {{ trans('labels.employee') }}
                @endif
            </a>
        </div>
    </div>
    @include('admin.theme.sidebarcontent')
</nav>
<!-- For Small Devices -->
<nav class="collapse collapse-horizontal sidebar sidebar-md" id="sidebarcollapse">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-white">
        <a href="{{ url(\App\Support\AdminPermissions::landing(Auth::user())) }}" class="text-white fs-4">
            @if (Auth::user()->type == 1)
                {{ trans('labels.admin_title') }}
            @elseif(Auth::user()->type == 4)
                {{ trans('labels.employee') }}
            @endif
        </a>
        <button class="btn text-white" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarcollapse"
            aria-expanded="false" aria-controls="sidebarcollapse"><i class="fa-light fa-xmark"></i></button>
    </div>
    @include('admin.theme.sidebarcontent')
</nav>
