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
                                    <th class="bg-light">@lang('Delivery Date')</th>
                                    <td>
                                        @if($originalOrder->delivery_date)
                                            {{ $originalOrder->delivery_date->format('Y-m-d H:i') }}
                                        @else
                                            <span class="text-muted">@lang('Not specified')</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">@lang('Stock Status')</th>
                                    <td>
                                        @if($originalOrder->in_stock)
                                            <span class="badge bg-success"><i class="fas fa-check-circle"></i> @lang('In Stock')</span>
                                        @else
                                            <span class="badge bg-warning"><i class="fas fa-exclamation-circle"></i> @lang('Out of Stock')</span>
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
                                    // Compatibilidad con ambos formatos: string JSON y array
                                    if (is_string($originalOrder->order_details)) {
                                        // Si es string, intentar decodificar JSON
                                        $details = json_decode($originalOrder->order_details, true);
                                    } elseif (is_array($originalOrder->order_details)) {
                                        // Si ya es array, usar directamente
                                        $details = $originalOrder->order_details;
                                    } else {
                                        // Fallback para otros tipos
                                        $details = null;
                                    }
                                @endphp
                                @if(is_array($details) && !empty($details))
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($details, JSON_PRETTY_PRINT) }}</pre>
                                @elseif(is_string($originalOrder->order_details))
                                    {{-- Mostrar string JSON tal como está si no se pudo decodificar --}}
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ $originalOrder->order_details }}</pre>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> @lang('No order details available')
                                    </div>
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
                                        <th>@lang('Code')</th>
                                        <th>@lang('Process')</th>
                                        <th>@lang('Sequence')</th>
                                        <th>@lang('Correction Factor')</th>
                                        <th>@lang('Time')</th>
                                        <th>@lang('Created')</th>
                                        <th>@lang('Finished At')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($originalOrder->processes->sortBy('sequence') as $process)
                                        @php
                                            $pivot = $process->pivot;
                                            $articles = $pivot->articles ?? collect();
                                            
                                            // Depurar los valores del pivot
                                            // Forzar la conversión a boolean
                                            $isFinished = (bool)$pivot->finished;
                                            
                                            $debugInfo = "Process ID: {$process->id}, Code: {$process->code}, ";
                                            $debugInfo .= "Pivot ID: {$pivot->id}, ";
                                            $debugInfo .= "finished (raw): {$pivot->finished}, ";
                                            $debugInfo .= "finished (bool): " . ($isFinished ? 'true' : 'false') . ", ";
                                            $debugInfo .= "finished_at: " . ($pivot->finished_at ?? 'null');
                                            
                                            // Asignar el valor convertido de vuelta al pivot
                                            $pivot->finished = $isFinished;
                                            
                                            // Escribir en el log para depuración
                                            \Log::info($debugInfo);
                                        @endphp
                                        <!-- Debug: {{ $debugInfo }} -->
                                        <tr>
                                            <td>{{ $process->code }}</td>
                                            <td>{{ $process->name }}</td>
                                            <td class="text-center">{{ $process->sequence }}</td>
                                            <td class="text-center">{{ number_format($process->factor_correccion, 2) }}</td>
                                            <td class="text-center">{{ $pivot->time ? number_format($pivot->time, 2) . 'h' : '-' }}</td>
                                            <td class="text-center">
                                                @if($pivot->created)
                                                    <span class="badge bg-success">@lang('Yes')</span>
                                                @else
                                                    <span class="badge bg-warning">@lang('No')</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($pivot->finished)
                                                    <span class="badge bg-success">{{ $pivot->finished_at ? $pivot->finished_at->format('Y-m-d H:i') : __('Finished') }}</span>
                                                @else
                                                    <span class="badge bg-warning">@lang('Pending')</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($articles->isNotEmpty())
                                            <tr class="articles-row">
                                                <td colspan="7" class="p-3 bg-light" style="border-top: 1px solid #e9ecef;">
                                                    <h6 class="mb-3 font-weight-bold"><i class="fas fa-cubes text-secondary mr-2"></i>@lang('Related Articles')</h6>
                                                    <table class="table table-sm table-hover mb-0 bg-white rounded">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th style="width: 30%;">@lang('Article Code')</th>
                                                                <th style="width: 50%;">@lang('Description')</th>
                                                                <th style="width: 20%;">@lang('Group')</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($articles as $article)
                                                                <tr>
                                                                    <td>{{ $article->codigo_articulo }}</td>
                                                                    <td>{{ $article->descripcion_articulo }}</td>
                                                                    <td>{{ $article->grupo_articulo }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">@lang('No processes associated with this order.')</td>
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
