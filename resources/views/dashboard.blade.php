@extends('layouts.admin')

@section('title', 'Dashboard Admin | YAPISTA HRIS')

@section('content')
    <x-page-header
        title="Dashboard"
        subtitle="Ringkasan kondisi pegawai dan kegiatan YAPISTA."
        :breadcrumbs="[['label' => 'Dashboard']]"
    >
        <x-slot:actions>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <i class="ti ti-user-plus" aria-hidden="true"></i>
                Tambah Pegawai
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="dashboard-grid">
        <div class="row g-3 mb-3" aria-label="Indikator utama">
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-kpi-card h-100">
                    <div class="card-body">
                        <div class="dashboard-kpi-heading">
                            <span class="dashboard-kpi-icon is-primary"><i class="ti ti-users" aria-hidden="true"></i></span>
                            <span class="dashboard-kpi-label">Total Pegawai</span>
                        </div>
                        <div class="dashboard-kpi-value">{{ number_format($dashboard['kpis']['totalEmployees']) }}</div>
                        <div class="dashboard-kpi-meta">Seluruh data pegawai terdaftar</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-kpi-card h-100">
                    <div class="card-body">
                        <div class="dashboard-kpi-heading">
                            <span class="dashboard-kpi-icon is-success"><i class="ti ti-user-check" aria-hidden="true"></i></span>
                            <span class="dashboard-kpi-label">Pegawai Aktif</span>
                        </div>
                        <div class="dashboard-kpi-value">{{ number_format($dashboard['kpis']['activeEmployees']) }}</div>
                        <div class="dashboard-kpi-meta">Berstatus kepegawaian aktif</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-kpi-card h-100">
                    <div class="card-body">
                        <div class="dashboard-kpi-heading">
                            <span class="dashboard-kpi-icon is-info"><i class="ti ti-calendar-event" aria-hidden="true"></i></span>
                            <span class="dashboard-kpi-label">Kegiatan Bulan Ini</span>
                        </div>
                        <div class="dashboard-kpi-value">{{ number_format($dashboard['kpis']['eventsThisMonth']) }}</div>
                        <div class="dashboard-kpi-meta">Semua status kegiatan bulan berjalan</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card dashboard-kpi-card h-100">
                    <div class="card-body">
                        <div class="dashboard-kpi-heading">
                            <span class="dashboard-kpi-icon is-warning"><i class="ti ti-chart-line" aria-hidden="true"></i></span>
                            <span class="dashboard-kpi-label">Rata-rata Kehadiran</span>
                        </div>
                        <div class="dashboard-kpi-value">{{ number_format($dashboard['kpis']['averageAttendance'], 1) }}%</div>
                        <div class="dashboard-kpi-meta">Maksimal 6 kegiatan terakhir</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="card dashboard-panel h-100">
                    <div class="card-header dashboard-panel-header">
                        <div>
                            <h5 class="mb-1">Pegawai per Unit Kerja</h5>
                            <p class="text-muted small mb-0">Distribusi pegawai aktif pada setiap unit YAPISTA.</p>
                        </div>
                        <a href="{{ route('employees.index') }}" class="btn btn-sm btn-light">Lihat Data</a>
                    </div>
                    <div class="card-body">
                        @if ($dashboard['institutionDistribution']['values'] !== [])
                            <div id="employee-unit-chart" class="dashboard-chart" role="img" aria-label="Diagram jumlah pegawai aktif per unit kerja"></div>
                        @else
                            <div class="empty-state dashboard-empty-state">
                                <span class="avtar avtar-sm bg-light-primary text-primary"><i class="ti ti-building-community" aria-hidden="true"></i></span>
                                <h5 class="mb-1">Belum ada data pegawai aktif</h5>
                                <p class="text-muted mb-0">Distribusi unit akan tampil setelah data pegawai tersedia.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card dashboard-panel h-100">
                    <div class="card-header dashboard-panel-header">
                        <div>
                            <h5 class="mb-1">Komposisi Pegawai</h5>
                            <p class="text-muted small mb-0">Pegawai aktif berdasarkan jenis pegawai.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($dashboard['employeeComposition']['values'] !== [])
                            <div id="employee-composition-chart" class="dashboard-chart" role="img" aria-label="Diagram komposisi pegawai aktif"></div>
                        @else
                            <div class="empty-state dashboard-empty-state">
                                <span class="avtar avtar-sm bg-light-secondary text-secondary"><i class="ti ti-chart-donut" aria-hidden="true"></i></span>
                                <h5 class="mb-1">Belum ada data komposisi</h5>
                                <p class="text-muted mb-0">Komposisi akan tampil setelah jenis pegawai tersedia.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card dashboard-panel h-100">
                    <div class="card-header dashboard-panel-header">
                        <div>
                            <h5 class="mb-1">Tren Kehadiran Kegiatan</h5>
                            <p class="text-muted small mb-0">Persentase kehadiran peserta aktif pada kegiatan terbaru.</p>
                        </div>
                        <a href="{{ route('reports.events') }}" class="btn btn-sm btn-light">Lihat Laporan</a>
                    </div>
                    <div class="card-body">
                        @if ($dashboard['attendanceTrend']['percentages'] !== [])
                            <div id="attendance-trend-chart" class="dashboard-chart" role="img" aria-label="Grafik tren persentase kehadiran kegiatan"></div>
                        @else
                            <div class="empty-state dashboard-empty-state">
                                <span class="avtar avtar-sm bg-light-info text-info"><i class="ti ti-calendar-stats" aria-hidden="true"></i></span>
                                <h5 class="mb-1">Belum ada data kehadiran</h5>
                                <p class="text-muted mb-0">Tren akan tampil setelah kegiatan memiliki peserta aktif.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card dashboard-panel h-100">
                    <div class="card-header dashboard-panel-header">
                        <div>
                            <h5 class="mb-1">Perlu Tindak Lanjut</h5>
                            <p class="text-muted small mb-0">Ringkasan pekerjaan operasional yang perlu diperiksa.</p>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="dashboard-insight-list">
                            <a href="{{ route('verifications.index', ['verification_status' => 'submitted']) }}" class="dashboard-insight-item">
                                <span class="dashboard-insight-icon is-warning"><i class="ti ti-user-question" aria-hidden="true"></i></span>
                                <span class="dashboard-insight-content"><strong>Menunggu Verifikasi</strong><small>Pengajuan data pegawai</small></span>
                                <span class="dashboard-insight-count">{{ number_format($dashboard['insights']['submittedEmployees']) }}</span>
                            </a>
                            <div class="dashboard-insight-item">
                                <span class="dashboard-insight-icon is-danger"><i class="ti ti-file-alert" aria-hidden="true"></i></span>
                                <span class="dashboard-insight-content"><strong>Dokumen Bermasalah</strong><small>Dokumen berstatus ditolak</small></span>
                                <span class="dashboard-insight-count">{{ number_format($dashboard['insights']['rejectedDocuments']) }}</span>
                            </div>
                            <div class="dashboard-insight-item">
                                <span class="dashboard-insight-icon is-danger"><i class="ti ti-clipboard-x" aria-hidden="true"></i></span>
                                <span class="dashboard-insight-content"><strong>Profil Perlu Perbaikan</strong><small>Review profil ditolak</small></span>
                                <span class="dashboard-insight-count">{{ number_format($dashboard['insights']['rejectedProfiles']) }}</span>
                            </div>
                            <a href="{{ route('events.index', ['status' => 'active']) }}" class="dashboard-insight-item">
                                <span class="dashboard-insight-icon is-info"><i class="ti ti-calendar-time" aria-hidden="true"></i></span>
                                <span class="dashboard-insight-content"><strong>Kegiatan Aktif Hari Ini</strong><small>Kegiatan yang sedang berjalan</small></span>
                                <span class="dashboard-insight-count">{{ number_format($dashboard['insights']['activeEventsToday']) }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page-scripts')
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
@endpush

@push('scripts')
    <script>
        (() => {
            if (typeof ApexCharts === 'undefined') {
                return;
            }

            const fontFamily = 'Public Sans, sans-serif';
            const labelColor = '#667382';
            const gridColor = '#e8edf2';
            const sharedChart = {
                chart: {
                    animations: { speed: 350 },
                    fontFamily,
                    foreColor: labelColor,
                    toolbar: { show: false },
                    zoom: { enabled: false }
                },
                dataLabels: { enabled: false },
                grid: { borderColor: gridColor, strokeDashArray: 4 },
                noData: { text: 'Belum ada data' }
            };

            const unitElement = document.querySelector('#employee-unit-chart');
            if (unitElement) {
                new ApexCharts(unitElement, {
                    ...sharedChart,
                    chart: { ...sharedChart.chart, type: 'bar', height: Math.max(300, {{ count($dashboard['institutionDistribution']['labels']) }} * 42) },
                    series: [{ name: 'Pegawai aktif', data: @json($dashboard['institutionDistribution']['values']) }],
                    colors: ['#4680ff'],
                    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '58%' } },
                    xaxis: {
                        categories: @json($dashboard['institutionDistribution']['labels']),
                        min: 0,
                        labels: { formatter: (value) => Math.round(value) }
                    },
                    yaxis: { labels: { maxWidth: 180 } },
                    tooltip: { y: { formatter: (value) => `${value} pegawai` } },
                    responsive: [{ breakpoint: 576, options: { yaxis: { labels: { maxWidth: 105 } } } }]
                }).render();
            }

            const compositionElement = document.querySelector('#employee-composition-chart');
            if (compositionElement) {
                new ApexCharts(compositionElement, {
                    ...sharedChart,
                    chart: { ...sharedChart.chart, type: 'donut', height: 330 },
                    series: @json($dashboard['employeeComposition']['values']),
                    labels: @json($dashboard['employeeComposition']['labels']),
                    colors: ['#4680ff', '#2ca87f', '#e58a00', '#6f42c1', '#3e8ef7', '#dc3545', '#6c757d', '#00acc1'],
                    legend: { position: 'bottom', fontSize: '12px', itemMargin: { horizontal: 8, vertical: 4 } },
                    plotOptions: { pie: { donut: { size: '66%', labels: { show: true, total: { show: true, label: 'Pegawai' } } } } },
                    stroke: { width: 2, colors: ['#ffffff'] },
                    tooltip: { y: { formatter: (value) => `${value} pegawai` } }
                }).render();
            }

            const trendElement = document.querySelector('#attendance-trend-chart');
            if (trendElement) {
                const attended = @json($dashboard['attendanceTrend']['attended']);
                const participants = @json($dashboard['attendanceTrend']['participants']);

                new ApexCharts(trendElement, {
                    ...sharedChart,
                    chart: { ...sharedChart.chart, type: 'line', height: 320 },
                    series: [{ name: 'Kehadiran', data: @json($dashboard['attendanceTrend']['percentages']) }],
                    colors: ['#2ca87f'],
                    stroke: { width: 3, curve: 'smooth' },
                    markers: { size: 4, strokeWidth: 2, hover: { size: 6 } },
                    xaxis: { categories: @json($dashboard['attendanceTrend']['labels']), labels: { trim: true, hideOverlappingLabels: true } },
                    yaxis: { min: 0, max: 100, tickAmount: 4, labels: { formatter: (value) => `${Math.round(value)}%` } },
                    tooltip: {
                        y: {
                            formatter: (value, context) => `${value}% (${attended[context.dataPointIndex]} dari ${participants[context.dataPointIndex]} peserta)`
                        }
                    }
                }).render();
            }
        })();
    </script>
@endpush
