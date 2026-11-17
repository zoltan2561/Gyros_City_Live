@extends('admin.theme.default')
@section('styles')
    @include('admin.newsletter.styles')
@endsection
@section('content')
<div class="container-fluid newsletter-admin">
    <a href="{{ route('admin.newsletter.index') }}" class="d-inline-block mb-3">&larr; {{ trans('newsletter.back') }}</a>
    <h1 class="h3">{{ trans('newsletter.title') }}</h1><p class="text-muted">{{ trans('newsletter.help') }}</p>
    @if ($errors->any())<div class="alert alert-danger" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <div id="newsletter-feedback" class="alert d-none" role="status" aria-live="polite"></div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 mb-4"><div class="card-body">
                <form id="newsletter-editor" method="post" action="{{ $letter->exists ? route('admin.newsletter.update', $letter) : route('admin.newsletter.store') }}">
                    @csrf
                    <label for="letter-subject" class="form-label">{{ trans('newsletter.subject') }}</label>
                    <input id="letter-subject" class="form-control mb-3" type="text" name="subject" maxlength="180" required value="{{ old('subject', $letter->subject) }}">
                    <label for="letter-body" class="form-label">{{ trans('newsletter.body') }}</label>
                    <textarea id="letter-body" class="form-control" name="body" rows="14" maxlength="20000" required aria-describedby="letter-body-help">{{ old('body', $letter->body) }}</textarea>
                    <p id="letter-body-help" class="small text-muted mt-2">{{ trans('newsletter.body_help') }}</p>
                    <div class="border rounded p-3 mb-3">
                        <label for="letter-image" class="form-label">{{ trans('newsletter.image') }}</label>
                        <input id="letter-image" class="form-control" type="file" accept="image/jpeg,image/png,image/gif,image/webp" aria-describedby="letter-image-help image-feedback">
                        <p id="letter-image-help" class="small text-muted mt-2">{{ trans('newsletter.image_help') }}</p>
                        <label for="letter-image-alt" class="form-label">{{ trans('newsletter.image_alt') }}</label>
                        <input id="letter-image-alt" class="form-control" type="text" maxlength="160" aria-describedby="letter-image-alt-help">
                        <p id="letter-image-alt-help" class="small text-muted mt-2">{{ trans('newsletter.image_alt_help') }}</p>
                        <button type="button" id="insert-image" class="btn btn-outline-primary" disabled>{{ trans('newsletter.image_insert') }}</button>
                        <p id="image-feedback" class="small mt-2 mb-0" role="status" aria-live="polite"></p>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ trans('newsletter.save') }}</button>
                </form>
            </div></div>
            <div class="card border-0 mb-4"><div class="card-body">
                <label for="test-email" class="form-label">{{ trans('newsletter.test_email') }}</label>
                <input type="email" id="test-email" class="form-control mb-3" maxlength="254" value="{{ Auth::user()->email }}">
                <button type="button" id="send-test" class="btn btn-outline-primary">{{ trans('newsletter.test') }}</button>
            </div></div>
            @if ($letter->exists)
            <div class="card border-0"><div class="card-body">
                <p><strong>{{ $recipientCount }}</strong> {{ trans('newsletter.subscribers') }}</p>
                <p class="small text-muted">{{ trans('newsletter.send_help') }}</p>
                <form method="post" action="{{ route('admin.newsletter.send', $letter) }}" id="newsletter-send">
                    @csrf
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="confirm-send" name="confirm" value="1" required><label class="form-check-label" for="confirm-send">{{ trans('newsletter.confirm') }}</label></div>
                    <button class="btn btn-primary" id="send-all" type="submit" @disabled($recipientCount === 0)>{{ trans('newsletter.send') }}</button>
                </form>
            </div></div>
            @endif
        </div>
        <div class="col-lg-6">
            <div class="card border-0"><div class="card-body">
                <h2 class="h5 mb-4">{{ trans('newsletter.preview') }}</h2>
                <div class="border rounded p-4" style="background:#fff;color:#211e1a;">
                    <p class="fw-bold">{{ config('mail.from.name') }}</p>
                    <h3 id="preview-subject" class="h4 text-break"></h3>
                    <div id="preview-body" style="white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.7;"></div>
                    <hr><p class="small text-muted">{{ trans('newsletter.footer') }}<br>{{ trans('newsletter.unsubscribe') }}</p>
                </div>
            </div></div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
(() => {
    const editor = document.getElementById('newsletter-editor');
    const subject = document.getElementById('letter-subject');
    const body = document.getElementById('letter-body');
    const feedback = document.getElementById('newsletter-feedback');
    const imageInput = document.getElementById('letter-image');
    const imageAlt = document.getElementById('letter-image-alt');
    const imageButton = document.getElementById('insert-image');
    const imageFeedback = document.getElementById('image-feedback');
    const testButton = document.getElementById('send-test');
    const saveButton = editor.querySelector('button[type="submit"]');
    const sendAll = document.getElementById('send-all');
    const imagePattern = new RegExp(@json(\App\Services\NewsletterContent::IMAGE_PATTERN), 'gu');
    let uploading = false;
    const savedSubject = @json($letter->subject ?? '');
    const savedBody = @json($letter->body ?? '').replace(/\r\n?/g, '\n');
    const updatePreview = () => {
        document.getElementById('preview-subject').textContent = subject.value;
        const preview = document.getElementById('preview-body');
        const content = document.createDocumentFragment();
        let offset = 0;
        for (const match of body.value.matchAll(imagePattern)) {
            content.append(document.createTextNode(body.value.slice(offset, match.index)));
            const image = document.createElement('img');
            image.src = match[2];
            image.alt = match[1];
            image.style.cssText = 'display:block;width:100%;max-width:100%;height:auto;margin:12px 0;border:0;';
            content.append(image);
            offset = match.index + match[0].length;
        }
        content.append(document.createTextNode(body.value.slice(offset)));
        preview.replaceChildren(content);
        if (sendAll) sendAll.disabled = uploading || @json($recipientCount === 0) || subject.value !== savedSubject || body.value !== savedBody;
    };
    editor.addEventListener('input', updatePreview);
    updatePreview();
    imageInput.addEventListener('change', () => {
        imageButton.disabled = !imageInput.files.length;
        imageFeedback.textContent = '';
        imageInput.removeAttribute('aria-invalid');
    });
    const uploadImage = async (file) => {
        if (uploading) return;
        if (!file || !['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            imageFeedback.textContent = @json(trans('newsletter.image_invalid'));
            imageFeedback.className = 'small mt-2 mb-0 text-danger';
            imageInput.setAttribute('aria-invalid', 'true');
            return;
        }
        const start = body.selectionStart;
        const end = body.selectionEnd;
        const alt = imageAlt.value.trim().replace(/[\[\]\r\n]/g, ' ');
        uploading = true;
        imageButton.disabled = imageInput.disabled = imageAlt.disabled = saveButton.disabled = testButton.disabled = true;
        body.readOnly = true;
        imageButton.setAttribute('aria-busy', 'true');
        imageFeedback.textContent = @json(trans('newsletter.image_uploading'));
        imageFeedback.className = 'small mt-2 mb-0 text-muted';
        updatePreview();
        let errorMessage = @json(trans('newsletter.image_failed'));
        try {
            const data = new FormData();
            data.set('_token', editor.querySelector('[name="_token"]').value);
            data.set('image', file);
            const response = await fetch(@json(route('admin.newsletter.images')), {method:'POST', body:data, headers:{'Accept':'application/json'}});
            const result = await response.json();
            if (!response.ok) {
                const errors = Object.values(result.errors || {}).flat();
                if (errors.length) errorMessage = errors.join(' ');
                throw new Error('Upload rejected');
            }
            const notation = '\n![' + alt + '](' + result.url + ')\n';
            if (body.value.length - (end - start) + notation.length > body.maxLength) {
                errorMessage = @json(trans('newsletter.image_body_full'));
                throw new Error('Message limit');
            }
            body.setRangeText(notation, start, end, 'end');
            imageInput.value = '';
            imageAlt.value = '';
            imageInput.removeAttribute('aria-invalid');
            imageFeedback.textContent = @json(trans('newsletter.image_inserted'));
            imageFeedback.className = 'small mt-2 mb-0 text-success';
            body.focus();
        } catch (error) {
            imageFeedback.textContent = errorMessage;
            imageFeedback.className = 'small mt-2 mb-0 text-danger';
        } finally {
            uploading = false;
            imageInput.disabled = imageAlt.disabled = saveButton.disabled = testButton.disabled = body.readOnly = false;
            imageButton.disabled = !imageInput.files.length;
            imageButton.removeAttribute('aria-busy');
            updatePreview();
        }
    };
    imageButton.addEventListener('click', () => uploadImage(imageInput.files[0]));
    body.addEventListener('paste', (event) => {
        const image = Array.from(event.clipboardData?.files || []).find(file => file.type.startsWith('image/'));
        if (image) { event.preventDefault(); uploadImage(image); }
    });
    editor.addEventListener('submit', (event) => { if (uploading) event.preventDefault(); });
    document.getElementById('send-test').addEventListener('click', async (event) => {
        if (!editor.reportValidity()) return;
        const email = document.getElementById('test-email');
        if (!email.value || !email.reportValidity()) { email.focus(); return; }
        const button = event.currentTarget;
        button.disabled = true;
        try {
            const data = new FormData(editor);
            data.set('test_email', email.value);
            const response = await fetch(@json(route('admin.newsletter.test')), {method:'POST', body:data, headers:{'Accept':'application/json'}});
            const result = await response.json();
            const errors = Object.values(result.errors || {}).flat();
            feedback.textContent = errors.length ? errors.join(' ') : result.message;
            feedback.className = 'alert ' + (response.ok ? 'alert-success' : 'alert-danger');
        } catch (error) {
            feedback.textContent = @json(trans('newsletter.mail_failed'));
            feedback.className = 'alert alert-danger';
        } finally { button.disabled = false; }
    });
    const sendForm = document.getElementById('newsletter-send');
    if (sendForm) sendForm.addEventListener('submit', () => { sendAll.disabled = true; });
})();
</script>
@endsection
