@extends('admin.theme.default')
@section('content')
<div class="container-fluid">
    <h1 class="h3">{{ trans('admin_access.profile') }}</h1>
    <p class="text-muted">{{ trans('admin_access.profile_help') }}</p>
    @if (!count(\App\Support\AdminPermissions::modules(Auth::user())))
        <div class="alert alert-info">{{ trans('admin_access.no_modules') }}</div>
    @endif
    <div class="row g-4">
        <div class="col-lg-6"><div class="card border-0"><div class="card-body">
            <h2 class="h5">{{ trans('labels.edit_profile') }}</h2>
            <form method="post" action="{{ url('admin/edit-profile') }}">
                @csrf
                @foreach (['name' => 'text', 'email' => 'email', 'mobile' => 'tel'] as $field => $type)
                    <div class="mb-3">
                        <label for="profile-{{ $field }}" class="form-label">{{ trans('labels.' . $field) }}</label>
                        <input type="{{ $type }}" name="{{ $field }}" id="profile-{{ $field }}" class="form-control" required
                            value="{{ old($field, Auth::user()->$field) }}" maxlength="{{ $field === 'email' ? 254 : ($field === 'mobile' ? 30 : 120) }}">
                        @error($field)<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <button class="btn btn-primary" type="submit">{{ trans('labels.save') }}</button>
            </form>
        </div></div></div>
        <div class="col-lg-6" id="change_password"><div class="card border-0"><div class="card-body">
            <h2 class="h5">{{ trans('labels.change_password') }}</h2>
            <form method="post" action="{{ url('admin/change-password') }}">
                @csrf
                @foreach (['oldpassword' => 'old_password', 'newpassword' => 'new_password', 'confirmpassword' => 'confirm_password'] as $field => $label)
                    <div class="mb-3">
                        <label for="profile-{{ $field }}" class="form-label">{{ trans('labels.' . $label) }}</label>
                        <input type="password" name="{{ $field }}" id="profile-{{ $field }}" class="form-control" required
                            autocomplete="{{ $field === 'oldpassword' ? 'current-password' : 'new-password' }}" maxlength="255" @if($field !== 'oldpassword') minlength="6" @endif>
                        @error($field)<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <button class="btn btn-primary" type="submit">{{ trans('labels.save') }}</button>
            </form>
        </div></div></div>
    </div>
</div>
@endsection
