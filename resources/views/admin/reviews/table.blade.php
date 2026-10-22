<table class="table table-striped align-middle">
    <thead><tr>
        <th>#</th><th>{{ trans('reviews.author') }}</th><th>{{ trans('labels.item_name') }}</th>
        <th>{{ trans('reviews.ratings') }}</th><th>{{ trans('labels.review') }}</th>
        <th>{{ trans('labels.status') }}</th><th>{{ trans('labels.created_date') }}</th><th>{{ trans('labels.action') }}</th>
    </tr></thead>
    <tbody>
        @forelse ($getreview as $review)
            <tr>
                <td>{{ $getreview->firstItem() + $loop->index }}</td>
                <td>{{ $review->user_info->name ?? trans('reviews.unknown_author') }}</td>
                <td>{{ $review->item_id ? ($review->item_info->item_name ?? trans('reviews.product')) : trans('reviews.restaurant') }}</td>
                <td class="text-nowrap"><i class="fa fa-star text-warning" aria-hidden="true"></i> {{ $review->ratting }}/5</td>
                <td style="min-width:180px;max-width:360px;white-space:normal;overflow-wrap:anywhere">{{ $review->comment }}</td>
                <td><span class="badge {{ (int)$review->status === 1 ? 'bg-success' : 'bg-warning text-dark' }}">{{ trans((int)$review->status === 1 ? 'reviews.published' : 'reviews.pending') }}</span></td>
                <td class="text-nowrap">{{ helper::date_format($review->created_at) }}</td>
                <td><div class="d-flex gap-2 flex-wrap">
                    <form method="post" action="{{ url('admin/reviews/status') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $review->id }}">
                        <input type="hidden" name="status" value="{{ (int)$review->status === 1 ? 2 : 1 }}">
                        <button class="btn btn-sm btn-outline-primary" type="submit">{{ trans((int)$review->status === 1 ? 'reviews.hide' : 'reviews.approve') }}</button>
                    </form>
                    <form method="post" action="{{ url('admin/reviews/destroy') }}" onsubmit="return confirm(@js(trans('reviews.delete_confirm')))">
                        @csrf
                        <input type="hidden" name="id" value="{{ $review->id }}">
                        <button class="btn btn-sm btn-outline-danger" type="submit">{{ trans('reviews.delete') }}</button>
                    </form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center py-4 text-muted">{{ trans('reviews.admin_empty') }}</td></tr>
        @endforelse
    </tbody>
</table>
{{ $getreview->withQueryString()->links() }}
