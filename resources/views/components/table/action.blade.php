<div class="d-flex gap-2">
    <button wire:click="$dispatch('user-edit', { id: {{ $item->id }} })" class="btn btn-warning">
        <i class="bi bi-pencil-square"></i>
    </button>
    <button wire:click="$dispatch('user-delete', { id: {{ $item->id }} })" wire:confirm="Delete this user?"
        class="btn btn-danger">
        <i class="bi bi-trash-fill"></i>
    </button>
</div>
