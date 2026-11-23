<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts::app_admin', ['title' => 'Lapor'])] class extends Component {
    //
};
?>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5>Lapor untuk {{ auth()->user()->isSiswa() ? auth()->user()->siswa->nama : 'Umum' }}</h5>
            </div>
        </div>
    </div>
</div>
