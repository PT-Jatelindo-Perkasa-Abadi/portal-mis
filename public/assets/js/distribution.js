let myDistributionChart = null;

$(document).ready(function () {
    if ($('#listmitra').length) {
        $('#listmitra').select2({
            placeholder: "Pilih Mitra Acquirer",
            width: '100%',
            allowClear: true
        });
        
        $('#listmitra').on('select2:select select2:clear', function () {
            $('input[name="search"]').val('');
        });
    }
});

function showLoadingShimmer() {
    document.getElementById('real-chart-container')?.classList.add('d-none');
    document.getElementById('real-table-body')?.classList.add('d-none');

    document.getElementById('shimmer-chart-container')?.classList.remove('d-none');
    document.getElementById('shimmer-table-body')?.classList.remove('d-none');
}

function hideLoadingShimmer(isApiError, chartRawData) {
    if (isApiError) {
        document.getElementById('shimmer-chart-container')?.classList.add('d-none');
        document.getElementById('real-chart-container')?.classList.remove('d-none');
        return;
    }

    document.getElementById('shimmer-chart-container')?.classList.add('d-none');
    document.getElementById('shimmer-table-body')?.classList.add('d-none');

    document.getElementById('real-chart-container')?.classList.remove('d-none');
    document.getElementById('real-table-body')?.classList.remove('d-none');

    if (!myDistributionChart && chartRawData) {
        initDistributionChart(chartRawData);
    }
}

function triggerReload(event) {
    event.preventDefault();
    showLoadingShimmer();
    setTimeout(function () {
        window.location.reload();
    }, 300);
}

function initDistributionChart(chartRawData) {
    const ctx = document.getElementById('distributionBarChart')?.getContext('2d');
    if (!ctx || !chartRawData) return;

    myDistributionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartRawData.labels || [],
            datasets: [
                {
                    label: 'Prepaid',
                    data: chartRawData.prepaid || [],
                    backgroundColor: '#B38705',
                    borderRadius: 2,
                    barThickness: 24,
                },
                {
                    label: 'Postpaid',
                    data: chartRawData.postpaid || [],
                    backgroundColor: '#FFC107',
                    borderRadius: 2,
                    barThickness: 24,
                },
                {
                    label: 'Non-Taglist',
                    data: chartRawData.non_taglist || [],
                    backgroundColor: '#FFECB5',
                    borderRadius: 2,
                    barThickness: 24,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#E7E8E8', drawTicks: false },
                    border: { display: false },
                    ticks: {
                        color: '#595C60',
                        padding: 12,
                        font: { family: 'Sora', size: 12 }
                    },
                    afterFit: function (scaleInstance) {
                        scaleInstance.width = 40;
                    }
                },
                x: {
                    border: { display: false },
                    ticks: {
                        color: '#595C60',
                        font: { family: 'Sora', size: 12, weight: '700' }
                    },
                    grid: { display: false }
                }
            }
        }
    });
}

window.selectService = function (val, text, element) {
    const hiddenInput = document.getElementById('value-service');
    if (hiddenInput) hiddenInput.value = val;

    const labelService = document.getElementById('label-service');
    if (labelService) {
        labelService.innerText = text;
        labelService.style.color = '#12161C';
        labelService.style.fontWeight = '600';
    }

    element.closest('.dropdown-menu')?.querySelectorAll('.custom-dropdown-item').forEach(el => {
        el.classList.remove('active');
        const icon = el.querySelector('svg');
        if (icon) icon.remove();
    });

    element.classList.add('active');
    element.insertAdjacentHTML('beforeend', '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#198754" class="bi bi-check-lg" viewBox="0 0 16 16"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg>');
};


document.addEventListener("DOMContentLoaded", function () {
    const chartContainer = document.getElementById('real-chart-container');
    const isApiError = chartContainer?.dataset.apiError === 'true';
    let chartRawData = null;

    if (chartContainer && chartContainer.dataset.chart) {
        try {
            chartRawData = JSON.parse(chartContainer.dataset.chart);
        } catch (e) {
            console.error("Gagal parse data chart JSON:", e);
        }
    }

    const form = document.getElementById('filterForm');
    if (form) {
        form.addEventListener('submit', function () {
            showLoadingShimmer();
        });
    }

    const dateInput = document.querySelector('input[name="date"]');
    const badgeDate = document.getElementById('badge-date');

    if (dateInput) {
        dateInput.addEventListener('input', function () {
            if (!this.value) {
                if (badgeDate) badgeDate.style.display = 'none';
                if (this.form) {
                    showLoadingShimmer();
                    this.form.submit();
                }
            }
        });
    }

    setTimeout(function () {
        hideLoadingShimmer(isApiError, chartRawData);
    }, 300);
});