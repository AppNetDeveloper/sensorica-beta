@extends('layouts.admin')

@section('title', __('Incident Details'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.production-order-incidents.index', $customer->id) }}">{{ __('Production Order Incidents') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Incident Details') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">@lang('Incident Details') - #{{ $incident->productionOrder->order_id }}</h3>
                    <div>
                        <a href="{{ route('customers.production-order-incidents.index', $customer->id) }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> @lang('Back to List')
                        </a>
                        @can('delete', $customer)
                        <form action="{{ route('customers.production-order-incidents.destroy', [$customer->id, $incident->id]) }}" 
                              method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('@lang('Are you sure you want to delete this incident?')')">
                                <i class="fas fa-trash"></i> @lang('Delete')
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>@lang('Incident Information')</h4>
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <th class="bg-light">@lang('Order ID')</th>
                                    <td>
                                        #{{ $incident->productionOrder->order_id }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Reason')</th>
                                    <td>{{ $incident->reason }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Created By')</th>
                                    <td>{{ $incident->createdBy ? $incident->createdBy->name : 'Sistema' }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Created At')</th>
                                    <td>{{ $incident->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Updated At')</th>
                                    <td>{{ $incident->updated_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4>@lang('Order Information')</h4>
                            <table class="table table-bordered table-striped">
                                <tr>
                                    <th class="bg-light">@lang('Centro')</th>
                                    <td>{{ $customer->name }}</td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Estado de orden')</th>
                                    <td>
                                        @if($incident->productionOrder->finished)
                                            <span class="badge bg-success">@lang('Finished')</span>
                                        @else
                                            <span class="badge bg-warning">@lang('In Progress')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Incident Status')</th>
                                    <td>
                                        @if($incident->productionOrder->status == 3)
                                            <span class="badge bg-danger">@lang('Incidencia activa')</span>
                                        @else
                                            <span class="badge bg-secondary">@lang('Incidencia finalizada')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Has Stock')</th>
                                    <td>
                                        @if($incident->productionOrder->has_stock)
                                            <span class="badge bg-success">@lang('Yes')</span>
                                        @else
                                            <span class="badge bg-danger">@lang('No')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Creado en ERP')</th>
                                    <td>{{ $incident->productionOrder->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($incident->notes)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h4 class="mb-0">@lang('Notes')</h4>
                                </div>
                                <div class="card-body">
                                    <div class="p-3 bg-light rounded">
                                        {!! nl2br(e($incident->notes)) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
