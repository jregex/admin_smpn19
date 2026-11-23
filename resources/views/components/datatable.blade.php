@props([
    'jsonData', // String JSON data dari Livewire
    'columns', // Array definisi kolom dari PHP
    'eventName', // Nama event listener untuk refresh (misal: 'refreshDatatable')
    'updateEvent', // Nama browser event untuk silent update (misal: 'update-guru-data')
    'hasAction' => true, // Apakah tabel ini memiliki tombol Edit/Hapus?
    'modalId' => 'defaultModal',
])
@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/tabulator/tabulator_bootstrap5.min.css') }}" />
    @if ($modalId == 'siswaModal' || $modalId == 'infoModal')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@6.0.0-beta.2/dist/dropzone.css" />
    @endif
    @if ($modalId == 'tahunModal' || $modalId == 'ekskulModal')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" />
    @endif
@endpush
<div>
    <div class="row g-2 align-items-center mb-3" wire:ignore>
        <div class="col-md-2">
            <select id="filter-field" class="form-select form-select-sm">
                <option value="">-- Pilih Kolom Filter --</option>
                @foreach ($columns as $col)
                    <option value="{{ $col['field'] }}">{{ $col['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input id="filter-value" type="text" class="form-control form-select-sm"
                placeholder="Ketik nilai filter...">
        </div>
        <div class="col-md-2">
            <button id="filter-clear" class="btn btn-sm btn-secondary">
                <i class="bi bi-x-square"></i>
            </button>
        </div>
    </div>

    <div wire:ignore>
        <div id="reusable-tabulator-table" class="rounded border-0"></div>
    </div>
</div>

@push('js')

    <script src="{{ asset('vendor/tabulator/tabulator.min.js') }}"></script>
    @if ($modalId == 'guruModal')
        <script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
        <script></script>
    @endif
    @if ($modalId == 'siswaModal' || $modalId == 'infoModal')
        <script src="https://cdn.jsdelivr.net/npm/dropzone@6.0.0-beta.2/dist/dropzone-min.js"></script>
        {{-- <script src="{{ asset('asset/js/custom-plugin.js') }}"></script> --}}
    @endif
    @if ($modalId == 'tahunModal' || $modalId == 'ekskulModal')
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
    @endif
    <script>
        let globalTabulatorInstance;

        document.addEventListener('livewire:init', () => {
            let rawData = {!! $jsonData !!};
            let phpColumns = @json($columns);
            let hasAction = {{ $hasAction ? 'true' : 'false' }};
            const modalElement = document.getElementById('{{ $modalId }}');
            let currentModalId = "{{ $modalId }}";
            let accessUpdateDelete =
                {{ auth()->user()->can('delete-ujian', App\Models\Ujian::class) ? 'true' : 'false' }}

            if (currentModalId === 'ekskulModal') {
                const siswaElement = document.querySelector('#tambahRombelModal');
                const siswaInstance = bootstrap.Modal.getOrCreateInstance(siswaElement, {
                    focus: false
                });
                window.addEventListener('open-modal-tambahRombelModal', () => siswaInstance.show());
                window.addEventListener('close-modal-tambahRombelModal', () => {
                    siswaInstance.hide()
                });
                siswaElement.addEventListener('hide.bs.modal', function() {
                    document.body.focus();

                    if (document.activeElement && document.activeElement !== document.body) {
                        document.activeElement.blur();
                    }
                });
                siswaElement.addEventListener('hidden.bs.modal', () => {
                    Livewire.dispatch('triggerCancel');
                });
            }
            if (currentModalId === 'infoModal') {
                const infoElement = document.querySelector('#detailInfoModal');
                const infoInstance = bootstrap.Modal.getOrCreateInstance(infoElement, {
                    focus: false
                });
                window.addEventListener('open-modal-detailInfoModal', () => infoInstance.show());
                window.addEventListener('close-modal-detailInfoModal', () => {
                    infoInstance.hide()
                });
                infoElement.addEventListener('hide.bs.modal', function() {
                    document.body.focus();

                    // 2. Sebagai cadangan, hilangkan status active element jika ada descendant lain
                    if (document.activeElement && document.activeElement !== document.body) {
                        document.activeElement.blur();
                    }
                });
                infoElement.addEventListener('hidden.bs.modal', () => {
                    Livewire.dispatch('triggerTutup');
                });
            }
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement, {
                    focus: false
                });

                window.addEventListener('open-modal-{{ $modalId }}', () => modalInstance.show());
                window.addEventListener('close-modal-{{ $modalId }}', () => {
                    modalInstance.hide()
                });
                modalElement.addEventListener('hide.bs.modal', function() {
                    document.body.focus();

                    // 2. Sebagai cadangan, hilangkan status active element jika ada descendant lain
                    if (document.activeElement && document.activeElement !== document.body) {
                        document.activeElement.blur();
                    }
                });
                modalElement.addEventListener('hidden.bs.modal', () => {
                    if (currentModalId === 'infoModal') {
                        Livewire.dispatch('triggerTutup');
                    }
                    Livewire.dispatch('triggerCancel');
                });
            }
            var fieldEl = document.getElementById("filter-field");
            var valueEl = document.getElementById("filter-value");

            function customFilter(data) {
                return data.car && data.rating < 3;
            }

            function updateFilter() {
                var filterVal = fieldEl.options[fieldEl.selectedIndex].value;
                var typeVal = "like";

                var filter = filterVal == "function" ? customFilter : filterVal;

                if (filterVal == "function") {
                    valueEl.disabled = true;
                } else {
                    valueEl.disabled = false;
                }

                if (filterVal) {
                    globalTabulatorInstance.setFilter(filter, typeVal, valueEl.value);
                }
            }

            document.getElementById("filter-field").addEventListener("change", updateFilter);
            document.getElementById("filter-value").addEventListener("keyup", updateFilter);

            //Clear filters on "Clear Filters" button click
            document.getElementById("filter-clear").addEventListener("click", function() {
                fieldEl.value = "";
                valueEl.value = "";

                globalTabulatorInstance.clearFilter();
            });

            const actionButtonsFormatter = function(cell, formatterParams, onRendered) {
                let rowData = cell.getRow().getData();
                let isSiswa = "{{ auth()->user()->isSiswa() }}";
                let detailButton = '';
                let startButton = '';
                let stopButton = '';
                let nilaiButton = '';
                if (currentModalId === 'modalDetailUjian' || currentModalId === 'infoModal') {
                    detailButton = `
                    <button class="btn btn-sm btn-secondary btn-detail" data-id="${rowData.id}" title="${currentModalId}">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                `;
                } else if (currentModalId === 'ekskulModal' && !isSiswa) {
                    detailButton = `
                    <button class="btn btn-sm btn-success btn-add" data-id="${rowData.id}" title="Rekrut anggota">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                `;
                } else {
                    detailButton = '';
                }
                if (currentModalId === 'modalDetailUjian' && rowData.status !== 'BERLANGSUNG' && rowData
                    .status !== 'SELESAI') {
                    startButton = `
                    <button class="btn btn-sm ${rowData.status !== 'BERLANGSUNG' && rowData.status !=='SELESAI' ? 'btn-success':'btn-secondary'} btn-start-ujian" data-id="${rowData.id}">
                            ${rowData.status !== 'BERLANGSUNG' && rowData.status !=='SELESAI' ? '<i class="bi bi-play-fill"></i>':'<i class="bi bi-pause-fill"></i>'}

                    </button>
                    `;
                }
                if (currentModalId === 'modalDetailUjian' && rowData.status === 'BERLANGSUNG' && rowData
                    .status !== 'SELESAI') {
                    stopButton = `
                    <button class="btn btn-sm ${rowData.status === 'BERLANGSUNG' && rowData.status !=='SELESAI' ? 'btn-danger':'btn-secondary'} btn-stop-ujian" data-id="${rowData.id}">
                            <i class="bi bi-stop-fill"></i>
                    </button>
                    `;
                }
                if (currentModalId === 'modalDetailUjian' && rowData.status === 'SELESAI') {
                    nilaiButton = `
                    <button class="btn btn-sm btn-primary btn-nilai-ujian" data-id="${rowData.id}" title="Lihat nilai siswa">
                            <i class="bi bi-bar-chart-fill"></i>
                    </button>
                    `;
                }
                if (hasAction) {
                    return `
                        <div class="d-flex gap-1">
                        ${startButton}
                        ${stopButton}
                        ${nilaiButton}
                        ${detailButton}
                            <button class="btn btn-sm btn-warning text-white btn-edit" data-id="${rowData.id}" data-status="${rowData.status}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="${rowData.id}">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    `;
                } else {
                    return `
                        <div class="d-flex gap-1">
                            ${detailButton}
                        </div>
                    `;
                }

            };

            let dynamicColumns = [{
                title: "No",
                field: "rownum",
                formatter: "rownum",
                width: 60,
                headerSort: false,
                hozAlign: "center",
                headerHozAlign: "center"
            }];

            phpColumns.forEach(col => {
                let columnConfig = {
                    title: col.label,
                    field: col.field,
                    sorter: "string",
                    hozAlign: "center",
                    headerHozAlign: "center"
                };

                if (col.searchable === false) {
                    columnConfig.headerFilter = false;
                }
                if (col.sortable === false) {
                    columnConfig.headerSort = false;
                }
                if (col.filterType === 'select') {
                    columnConfig.headerFilter = "select";
                    columnConfig.headerFilterFunc =
                        "="; // Pencarian harus cocok persis dengan opsi dropdown
                    columnConfig.headerFilterParams = {
                        // Mengubah array PHP menjadi format yang dipahami dropdown Tabulator
                        values: true,
                    };
                    columnConfig.headerFilterPlaceholder = "Pilih...";
                } else {
                    columnConfig.headerFilter = "input";
                    columnConfig.headerFilterPlaceholder = "Cari...";
                }
                if (col.field === 'foto') {
                    columnConfig.width = 100;
                    columnConfig.hozAlign = "center";
                    columnConfig.headerHozAlign = "center";
                    columnConfig.headerSort = false;
                    columnConfig.headerFilter = false;
                    columnConfig.formatter = function(cell) {
                        const fileName = cell.getValue();
                        const src = fileName ? `/gambar/images/siswa/${fileName}` :
                            '/gambar/images/siswa/default-avatar.png';
                        return `<img src="${src}" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">`;
                    };
                }
                if (currentModalId === 'sekolahModal' && col.field === 'logo') {
                    columnConfig.width = 100;
                    columnConfig.hozAlign = "center";
                    columnConfig.headerHozAlign = "center";
                    columnConfig.headerSort = false;
                    columnConfig.headerFilter = false;
                    columnConfig.formatter = function(cell) {
                        const fileName = cell.getValue();
                        const src = fileName ? `/gambar/images/logo/${fileName}` :
                            '/gambar/images/logo/default.png';
                        return `<img src="${src}" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover;">`;
                    };
                }
                if (col.field === 'is_aktif') {
                    columnConfig.width = 80;
                    columnConfig.hozAlign = "center";
                    columnConfig.headerHozAlign = "center";
                    columnConfig.headerSort = false;
                    columnConfig.headerFilter = false;
                    columnConfig.formatter = function(cell) {
                        const status = cell.getValue();
                        const icon = status ? `bi-check-circle-fill text-success` :
                            'bi-check-circle text-secondary';
                        return `<i class="nav-icon fs-5 bi ${icon}"></i>`;
                    };
                }
                if (col.field === 'nama_sekolah' && currentModalId !== 'sekolahModal') {
                    columnConfig.headerSort = false;
                    columnConfig.headerFilter = false;
                }
                if (col.field === 'total_rombel') {
                    columnConfig.headerFilter = false;
                }

                dynamicColumns.push(columnConfig);
                // dynamicColumns.push({
                //     title: col.label, // Judul kolom yang rapi (misal: 'Nama Guru')
                //     field: col.field, // Kunci data array (misal: 'nama')
                //     sorter: "string",
                //     headerFilter: disableFilterFields.includes(col.field) ? false : "input"
                // });
            });

            if (hasAction || currentModalId === 'ekskulModal' || currentModalId === 'modalDetailUjian' ||
                currentModalId === 'infoModal') {
                dynamicColumns.push({
                    title: "Aksi",
                    headerSort: false,
                    hozAlign: "center",
                    width: 200,
                    formatter: actionButtonsFormatter
                });
            }

            globalTabulatorInstance = new Tabulator("#reusable-tabulator-table", {
                data: rawData,
                layout: "fitColumns",
                responsiveLayout: "collapse",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [10, 15, 25, 50],
                placeholder: "<div class='text-center p-3 text-muted'>Tidak ada data ditemukan</div>",
                columns: dynamicColumns,
                elementAttributes: {
                    className: "table table-striped",
                },
            });

            document.getElementById("reusable-tabulator-table").addEventListener("click", function(e) {
                let target = e.target.closest('button');
                if (!target) return;

                let id = target.getAttribute('data-id');
                if (target.classList.contains('btn-start-ujian')) {
                    // window.location.href = `/admin/ujians/start/${id}`;
                    e.preventDefault();

                    // 1. Buat elemen form secara dinamis (hidden form)
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/ujians/start/${id}`;

                    let tokenElement = document.querySelector('meta[name="csrf-token"]');
			let csrfToken = tokenElement ? tokenElement.getAttribute('content') : '';
                    if (csrfToken) {
                        let csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = csrfToken;
                        form.appendChild(csrfInput);
                    } else {
                        console.error(
                            "CSRF Token tidak ditemukan! Pastikan <meta name='csrf-token'> sudah ada di <head>."
                        );
                    }

                    // 3. Sisipkan form ke body dan lakukan redirect POST secara fisik
                    document.body.appendChild(form);
                    form.submit();

                    return;
                    //Livewire.dispatch('startUjian', {
                    //   id: id
                    //});
                    //return;
                }
                if (target.classList.contains('btn-stop-ujian')) {
                    // window.location.href = `/admin/ujians/start/${id}`;
                    e.preventDefault();

                    // 1. Buat elemen form secara dinamis (hidden form)
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/admin/ujians/stop/${id}`;

                    // 2. Ambil token CSRF Laravel dari meta tag
                    let tokenElement = document.querySelector('meta[name="csrf-token"]');
		let csrfToken = tokenElement ? tokenElement.getAttribute('content') : '';
                    if (csrfToken) {
                        let csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = csrfToken;
                        form.appendChild(csrfInput);
                    } else {
                        console.error(
                            "CSRF Token tidak ditemukan! Pastikan <meta name='csrf-token'> sudah ada di <head>."
                        );
                    }

                    // 3. Sisipkan form ke body dan lakukan redirect POST secara fisik
                    document.body.appendChild(form);
                    form.submit();

                    return;
                }

                if (target.classList.contains('btn-edit')) {
                    // Memicu event dinamis ke Livewire (misal: 'editGuru' atau 'editSiswa')
                    if (currentModalId === 'modalDetailUjian') {
                        window.location.href = `/admin/ujians/edit/${id}`;
                    } else {

                        Livewire.dispatch('editRow', {
                            id: id
                        });
                    }
                }
                if (target.classList.contains('btn-add')) {
                    // Memicu event dinamis ke Livewire (misal: 'editGuru' atau 'editSiswa')
                    Livewire.dispatch('addRombelRow', {
                        id: id
                    });
                }
                if (target.classList.contains('btn-delete')) {
                    Livewire.dispatch('triggerDeleteConfirm', {
                        id: id
                    });
                }
                if (target.classList.contains('btn-detail')) {
                    Livewire.dispatch('detailModal', {
                        id: id
                    });
                }
                if (target.classList.contains('btn-nilai-ujian')) {
                    Livewire.dispatch('lihatNilaiUjian', {
                        id: id
                    });
                }

                // if (target.classList.contains('btn-delete')) {
                //     if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                //         Livewire.dispatch('deleteRow', {
                //             id: id
                //         });
                //     }
                // }
            });
        });

        // 6. Tangkap Sinyal Senyap Update Data dari Backend
        window.addEventListener('{{ $updateEvent }}', event => {
            let freshData = event.detail.data;
            if (globalTabulatorInstance && freshData) {
                globalTabulatorInstance.setData(freshData);
            }
        });
    </script>
@endpush
