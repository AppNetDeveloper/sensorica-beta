<div class="d-flex">
    <a href="{{ route('customers.original-orders.show', [$customer->id, $order->id]) }}" 
       class="btn btn-sm btn-info me-2" data-bs-toggle="tooltip" title="@lang('View')">
        <i class="fas fa-eye"></i>
    </a>
    @can('original-order-edit')
    <a href="{{ route('customers.original-orders.edit', [$customer->id, $order->id]) }}" 
       class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="@lang('Edit')">
        <i class="fas fa-edit"></i>
    </a>
    @endcan
    @can('original-order-delete')
    <form action="{{ route('customers.original-orders.destroy', [$customer->id, $order->id]) }}" 
          method="POST" style="display: inline-block;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-danger" 
                data-bs-toggle="tooltip" title="@lang('Delete')" 
                onclick="return confirm('@lang('Are you sure you want to delete this order?')')">
            <i class="fas fa-trash"></i>
        </button>
    </form>
    @endcan
</div>
