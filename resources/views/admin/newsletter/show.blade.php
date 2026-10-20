@extends('admin.theme.default')
@section('styles')
    @include('admin.newsletter.styles')
@endsection
@section('content')
<div class="container-fluid newsletter-admin">
    <a href="{{ route('admin.newsletter.index') }}" class="d-inline-block mb-3">&larr; {{ trans('newsletter.back') }}</a>
    <h1 class="h3 text-break">{{ $letter->subject }}</h1>
    <p id="sending-state">{{ trans('newsletter.' . $letter->status) }}</p>
    <div class="row g-3 mb-4" aria-live="polite">
        @foreach (['total', 'sent', 'failed', 'skipped', 'pending'] as $key)
            <div class="col-6 col-lg"><div class="card border-0 h-100"><div class="card-body"><strong class="fs-3" id="progress-{{ $key }}">{{ $progress[$key] }}</strong><p class="small mb-0">{{ trans('newsletter.' . $key) }}</p></div></div></div>
        @endforeach
    </div>
    <div class="alert alert-warning {{ $progress['failed'] ? '' : 'd-none' }}" id="failed-info">{{ trans('newsletter.failed_help') }}</div>
    <div class="alert alert-warning d-none" id="sending-error" role="alert"></div>
    @if ($letter->status === 'sending')
        <p id="processing-help">{{ trans('newsletter.processing_help') }}</p>
        <button type="button" class="btn btn-primary mb-4" id="continue-sending">{{ trans('newsletter.resume') }}</button>
    @endif
    <div class="card border-0"><div class="card-body"><div style="white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.7;">{{ $letter->body }}</div></div></div>
</div>
@endsection
@section('script')
@if ($letter->status === 'sending')
<script>
(() => {
    const button = document.getElementById('continue-sending');
    const errorBox = document.getElementById('sending-error');
    let active = false;
    const run = async () => {
        if (active) return;
        active = true;
        button.disabled = true;
        button.textContent = @json(trans('newsletter.working'));
        errorBox.classList.add('d-none');
        try {
            const response = await fetch(@json(route('admin.newsletter.process', $letter)), {method:'POST', headers:{'Accept':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}});
            if (!response.ok) throw new Error('Sending interrupted');
            const progress = await response.json();
            ['total', 'sent', 'failed', 'skipped', 'pending'].forEach(key => { document.getElementById('progress-' + key).textContent = progress[key]; });
            document.getElementById('failed-info').classList.toggle('d-none', !progress.failed);
            if (progress.finished) {
                document.getElementById('sending-state').textContent = @json(trans('newsletter.completed'));
                button.classList.add('d-none');
                document.getElementById('processing-help').classList.add('d-none');
            } else if (progress.pending === 0 && progress.sending > 0) {
                errorBox.textContent = @json(trans('newsletter.inflight_help'));
                errorBox.classList.remove('d-none');
            } else { setTimeout(run, 700); }
        } catch (error) {
            errorBox.textContent = @json(trans('newsletter.process_failed'));
            errorBox.classList.remove('d-none');
        } finally {
            active = false;
            button.disabled = false;
            button.textContent = @json(trans('newsletter.resume'));
        }
    };
    button.addEventListener('click', run);
    run();
})();
</script>
@endif
@endsection
