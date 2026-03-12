// assets/js/chart.js - Chart.js integration untuk laporan penjualan

class SalesChart {
    constructor() {
        this.charts = new Map();
        this.init();
    }

    init() {
        this.setupSalesChart();
        this.setupRevenueChart();
        this.setupProductChart();
    }

    // Setup chart penjualan harian/bulanan
    setupSalesChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        // Data dummy untuk contoh
        const data = {
            labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            datasets: [{
                label: 'Penjualan Harian',
                data: [1200000, 1900000, 1500000, 2100000, 1800000, 2500000, 2200000],
                backgroundColor: 'rgba(79, 70, 229, 0.2)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        };

        this.charts.set('salesChart', new Chart(ctx, {
            type: 'line',
            data: data,
            options: this.getChartOptions('Penjualan 7 Hari Terakhir')
        }));
    }

    // Setup chart revenue
    setupRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;

        const data = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
            datasets: [{
                label: 'Pendapatan Bulanan',
                data: [45000000, 52000000, 48000000, 61000000, 55000000, 68000000],
                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 2
            }]
        };

        this.charts.set('revenueChart', new Chart(ctx, {
            type: 'bar',
            data: data,
            options: this.getChartOptions('Pendapatan Bulanan')
        }));
    }

    // Setup chart produk terlaris
    setupProductChart() {
        const ctx = document.getElementById('productChart');
        if (!ctx) return;

        const data = {
            labels: ['Buku Tulis', 'Pensil', 'Bolpoin', 'Penghapus', 'Penggaris'],
            datasets: [{
                label: 'Jumlah Terjual',
                data: [45, 32, 28, 15, 12],
                backgroundColor: [
                    'rgba(79, 70, 229, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(139, 92, 246, 0.8)'
                ],
                borderColor: [
                    'rgba(79, 70, 229, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(139, 92, 246, 1)'
                ],
                borderWidth: 1
            }]
        };

        this.charts.set('productChart', new Chart(ctx, {
            type: 'bar',
            data: data,
            options: this.getChartOptions('Produk Terlaris')
        }));
    }

    // Common chart options
    getChartOptions(title) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: title
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                if (context.parsed.y >= 1000) {
                                    label += 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                                } else {
                                    label += context.parsed.y;
                                }
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                            return value;
                        }
                    }
                }
            }
        };
    }

    // Update chart dengan data baru
    updateChart(chartId, newData) {
        const chart = this.charts.get(chartId);
        if (chart) {
            chart.data.labels = newData.labels;
            chart.data.datasets[0].data = newData.values;
            chart.update();
        }
    }

    // Destroy chart
    destroyChart(chartId) {
        const chart = this.charts.get(chartId);
        if (chart) {
            chart.destroy();
            this.charts.delete(chartId);
        }
    }

    // Export chart sebagai image
    exportChartAsImage(chartId, filename = 'chart') {
        const chart = this.charts.get(chartId);
        if (chart) {
            const image = chart.toBase64Image();
            const link = document.createElement('a');
            link.href = image;
            link.download = `${filename}.png`;
            link.click();
        }
    }

    // Load real data dari API
    async loadSalesData(period = 'week') {
        try {
            const response = await fetch(`api/sales.php?period=${period}`);
            const data = await response.json();
            
            if (this.charts.has('salesChart')) {
                this.updateChart('salesChart', {
                    labels: data.labels,
                    values: data.values
                });
            }
            
            return data;
        } catch (error) {
            console.error('Error loading sales data:', error);
            this.showError('Gagal memuat data penjualan');
        }
    }

    showError(message) {
        if (window.kasirApp && window.kasirApp.showNotification) {
            window.kasirApp.showNotification(message, 'error');
        } else {
            alert(message);
        }
    }
}

// Initialize charts ketika DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Load Chart.js dari CDN jika belum ada
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = function() {
            window.salesChart = new SalesChart();
        };
        document.head.appendChild(script);
    } else {
        window.salesChart = new SalesChart();
    }
});

// Utility functions untuk charts
function changeChartPeriod(period) {
    if (window.salesChart) {
        window.salesChart.loadSalesData(period);
    }
}

function exportChart(chartId) {
    if (window.salesChart) {
        window.salesChart.exportChartAsImage(chartId);
    }
}

// Contoh data untuk testing
const sampleChartData = {
    weekly: {
        labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        values: [1200000, 1900000, 1500000, 2100000, 1800000, 2500000, 2200000]
    },
    monthly: {
        labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
        values: [8500000, 9200000, 7800000, 9500000]
    }
};