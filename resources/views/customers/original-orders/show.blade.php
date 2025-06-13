@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">@lang('Order Details') - {{ $originalOrder->order_id }}</h3>
                    <div>
                        <a href="{{ route('customers.original-orders.index', $customer->id) }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> @lang('Back to List')
                        </a>
                        @can('original-order-edit')
                        <a href="{{ route('customers.original-orders.edit', [$customer->id, $originalOrder->id]) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-edit"></i> @lang('Edit')
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>@lang('Order Information')</h4>
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <th class="bg-light">@lang('Order ID')</th>
                                    <td>{{ $originalOrder->order_id }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Client Number')</th>
                                    <td>{{ $originalOrder->client_number }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Processed')</th>
                                    <td>
                                        @if($originalOrder->processed)
                                            <span class="badge bg-success">@lang('Yes')</span>
                                        @else
                                            <span class="badge bg-warning">@lang('No')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Created At')</th>
                                    <td>{{ $originalOrder->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Updated At')</th>
                                    <td>{{ $originalOrder->updated_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Finished At')</th>
                                    <td>
                                        @if($originalOrder->finished_at)
                                            <span class="badge bg-success">{{ $originalOrder->finished_at->format('Y-m-d H:i') }}</span>
                                        @else
                                            <span class="badge bg-info">@lang('Pending')</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4>@lang('Order Details')</h4>
                            <div class="bg-light p-3 rounded border">
                                @php
                                    $details = json_decode($originalOrder->order_details, true);
                                @endphp
                                @if(is_array($details))
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($details, JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> @lang('Invalid JSON format')
                                    </div>
                                    {{ $originalOrder->order_details }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h4>@lang('Associated Processes')</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="bg-light">
                                    <tr>
                                        <th>@lang('Process')</th>
                                        <th>@lang('Created')</th>
                                        <th>@lang('Finished At')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($originalOrder->processes as $process)
                                        <tr>
                                            <td>{{ $process->name }}</td>
                                            <td class="text-center">
                                                @if($process->pivot->created)
                                                    <span class="badge bg-success">@lang('Yes')</span>
                                                @else
                                                    <span class="badge bg-warning">@lang('No')</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($process->pivot->finished_at)
                                                    <span class="badge bg-success">{{ $process->pivot->finished_at->format('Y-m-d H:i') }}</span>
                                                @else
                                                    <span class="badge bg-warning">@lang('Pending')</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">@lang('No processes associated with this order.')</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
