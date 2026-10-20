@extends('admin.theme.default')
@section('styles')
    @include('admin.newsletter.styles')
@endsection
@section('content')
<div class="container-fluid newsletter-admin">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4 align-items-center">
        <div><h1 class="h3 mb-2">{{ trans('newsletter.title') }}</h1><p class="text-muted mb-0">{{ trans('newsletter.help') }}</p></div>
        <a href="{{ route('admin.newsletter.create') }}" class="btn btn-primary">{{ trans('newsletter.new') }}</a>
    </div>
    <div class="card border-0 mb-4"><div class="card-body"><strong class="fs-3">{{ $recipientCount }}</strong><span class="ms-2">{{ trans('newsletter.subscribers') }}</span></div></div>
    <div class="card border-0"><div class="card-body">
        <h2 class="h5 mb-3">{{ trans('newsletter.history') }}</h2>
        <div class="table-responsive"><table class="table align-middle">
            <thead><tr><th>{{ trans('newsletter.subject') }}</th><th>{{ trans('newsletter.status') }}</th><th>{{ trans('newsletter.created') }}</th><th></th></tr></thead>
            <tbody>@forelse ($letters as $letter)
                <tr><td class="text-break">{{ $letter->subject }}</td><td><span class="badge {{ $letter->status === 'completed' ? 'bg-success' : ($letter->status === 'sending' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ trans('newsletter.' . $letter->status) }}</span></td><td>{{ $letter->created_at->format('Y-m-d H:i') }}</td>
                    <td><a class="btn btn-outline-primary btn-sm" href="{{ route($letter->status === 'draft' ? 'admin.newsletter.edit' : 'admin.newsletter.show', $letter) }}">{{ trans('newsletter.open') }}</a></td></tr>
            @empty
                <tr><td colspan="4">{{ trans('newsletter.empty') }}</td></tr>
            @endforelse</tbody>
        </table></div>
        {{ $letters->links() }}
    </div></div>
</div>
@endsection
