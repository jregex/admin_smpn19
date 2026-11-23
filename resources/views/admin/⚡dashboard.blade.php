<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts::app_admin', ['title' => 'Dashboard'])] class extends Component {
    public $chartLabels = [];
    public $chartData = [];
    public $jmlSiswa = '';
    public function mount()
    {
        $user = auth()->user();

        // Agregasi jumlah siswa per tahun ajaran langsung dari tabel siswa
        $dataSiswa = Siswa::select('tahun_ajarans.tahun', DB::raw('count(siswas.id) as total_siswa'))->join('rombels', 'siswas.rombel_id', '=', 'rombels.id')->join('tahun_ajarans', 'rombels.tahun_ajaran_id', '=', 'tahun_ajarans.id')->where('rombels.sekolah_id', $user->sekolah_id)->groupBy('tahun_ajarans.tahun', 'rombels.tahun_ajaran_id', 'tahun_ajarans.tanggal_mulai')->orderBy('tahun_ajarans.tanggal_mulai', 'asc')->get();

        // Format data untuk Chart.js
        $this->chartLabels = $dataSiswa->pluck('tahun')->toArray();
        $this->chartData = $dataSiswa->pluck('total_siswa')->toArray();
        $this->jmlSiswa = $dataSiswa->sum('total_siswa');
    }
};
?>

<div>
    @if (session()->has('error'))
        <div class="row mb-3">
            <div class="alert alert-warning mb-0 py-2">
                <i class="bi bi-info-circle"></i>
                <strong>{{ session()->get('error') }}.</strong>
            </div>
        </div>
    @endif
    <div class="row">
        <!-- /.col -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon text-bg-warning shadow-sm">
                    <i class="bi bi-people-fill"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text">Jumlah Siswa</span>
                    <span class="info-box-number">{{ $jmlSiswa }}</span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-8">

            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom bg-white py-3">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-graph-up-arrow text-primary me-2"></i>Jumlah Siswa Aktif Per Tahun Ajaran
                    </h6>
                </div>
                <div class="card-body">
                    <div wire:ignore>
                        <div id="siswaApexChart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('js')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@5.12.0/dist/apexcharts.min.js"></script>
    <script>
        document.addEventListener('livewire:navigated', function() {
            const element = document.getElementById('siswaApexChart');
            if (!element) return;

            // Ambil data dari backend Livewire
            const categories = @json($chartLabels);
            const dataSeries = @json($chartData);

            const options = {
                chart: {
                    type: 'bar', // Ubah menjadi 'line' atau 'area' jika ingin mengganti tipe grafik
                    height: 350,
                    toolbar: {
                        show: false
                    }, // Sembunyikan tombol download menu untuk tampilan minimalis
                    fontFamily: 'inherit'
                },
                series: [{
                    name: 'Siswa Aktif',
                    data: dataSeries
                }],
                colors: ['#0d6efd'], // Warna Bootstrap Primary
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '45%',
                        distributed: false,
                        dataLabels: {
                            position: 'top'
                        } // Letakkan angka jumlah di atas batang grafik
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val + " Siswa";
                    },
                    offsetY: -20,
                    style: {
                        fontSize: '12px',
                        colors: ["#304758"]
                    }
                },
                grid: {
                    borderColor: '#e9ecef',
                    strokeDashArray: 5,
                    xaxis: {
                        lines: {
                            show: false
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                xaxis: {
                    categories: categories,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    title: {
                        text: 'Total Siswa'
                    },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0);
                        } // Membaca angka sebagai bilangan bulat
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " Siswa Terdaftar";
                        }
                    }
                }
            };

            const chart = new ApexCharts(element, options);
            chart.render();
        });
    </script>
@endpush
