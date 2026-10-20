@extends('admin.theme.default')
@section('content')
    @include('admin.breadcrumb')
    <div class="container-fluid">
        @if ((int) Auth::user()->type === 1)
            <div class="d-flex justify-content-end mb-3"><a class="btn btn-primary" href="{{ route('admin.newsletter.index') }}">{{ trans('newsletter.title') }}</a></div>
        @endif
        <div class="row">
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-body">
                        <div class="table-responsive" id="table-display">
                            <table class="table table-striped table-bordered zero-configuration">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('labels.email') }}</th>
                                        <th>{{ trans('labels.created_date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i=1; @endphp
                                    @foreach ($list as $value)
                                        <tr>
                                            <td>@php echo $i++; @endphp</td>
                                            <td>{{ $value->email }}</td>
                                            <td>
                                                {{ helper::date_format($value->created_at) }} <br>
                                                {{ helper::time_format($value->created_at) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
