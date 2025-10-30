@extends('layouts.admin')

@section('title', __('Optimal Times'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ $customer->name }} - {{ __('Optimal Times') }}</li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>{{ __('Optimal Times') }} — {{ $customer->name }}
                </h4>
                <div>
                    @if($canEditSettings)
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#optimalTimeSettingsModal">
                            <i class="fas fa-sliders-h"></i> {{ __('Optimal Time Settings') }}
                        </button>
                    @endif
                    <a href="{{ route('customers.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="optimalTimesTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{{ __('Sensor') }}</th>
                                <th>{{ __('Production Line') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Sensor Type') }}</th>
                                <th>{{ __('Optimal Time (auto calcul)') }}</th>
                                <th>{{ __('Optimal Time Sensor (actual)') }}</th>
                                <th>{{ __('Multiplier') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canEditSettings)
    <div class="modal fade" id="optimalTimeSettingsModal" tabindex="-1" aria-labelledby="optimalTimeSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="optimalTimeSettingsModalLabel">
                        <i class="fas fa-sliders-h me-2"></i>{{ __('Optimal Time Settings') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <form method="POST" action="{{ route('customers.optimal-sensor-times.settings', $customer->id) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="min_sample_size" class="form-label">{{ __('Minimum Sample Size') }}</label>
                            <input type="number"
                                   class="form-control"
                                   id="min_sample_size"
                                   name="min_sample_size"
                                   value="{{ $minSampleSize }}"
                                   min="1"
                                   max="100000"
                                   required>
                            <small class="form-text text-muted">{{ __('Minimum number of samples required before the automatic optimal time calculation can update values.') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>{{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            var table = $('#optimalTimesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('customers.optimal-sensor-times.index', $customer->id) }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'sensor_name', name: 'sensor_name' },
                    { data: 'production_line_name', name: 'production_line_name' },
                    { data: 'product_name', name: 'product_name' },
                    { data: 'sensor_type_formatted', name: 'sensor_type', orderable: true, searchable: false },
                    { data: 'optimal_time_formatted', name: 'optimal_time', orderable: true },
                    { data: 'sensor_optimal_time', name: 'sensor_optimal_time', orderable: true },
                    { data: 'sensor_multiplier', name: 'sensor_multiplier', orderable: true },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                responsive: true,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() == 'es' ? 'es-ES' : 'en-GB' }}.json"
                },
                order: [[0, 'desc']]
            });

            // Delete button handler
            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');
                var url = $(this).data('url');
                
                Swal.fire({
                    title: '{{ __('Are you sure?') }}',
                    text: '{{ __('You will not be able to recover this optimal time!') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '{{ __('Yes, delete it!') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire(
                                        '{{ __('Deleted!') }}',
                                        response.message,
                                        'success'
                                    );
                                    table.ajax.reload();
                                } else {
                                    Swal.fire(
                                        '{{ __('Error') }}',
                                        response.message,
                                        'error'
                                    );
                                }
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    '{{ __('Error') }}',
                                    '{{ __('An error occurred while deleting') }}',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
