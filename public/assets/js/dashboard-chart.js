class DashboardChart {

    static STATE = {
        CHART: 'chart',
        LOADING: 'loading',
        EMPTY: 'empty',
        ERROR: 'error'
    };

    constructor(options) {
        this.canvas = $(options.canvas);
        this.wrapper = this.canvas.closest('.chart-card');

        this.chart = new Chart(this.canvas[0], {
            type: options.type,
            data: options.data || {
                labels: [],
                datasets: []
            },
            options: options.options || {}
        });

        this.autoScaleY = options.autoScaleY !== false;

        this.loading = this.wrapper.find('.chart-loading');
        this.empty = this.wrapper.find('.chart-empty');
        this.error = this.wrapper.find('.chart-error');

        this.reloadButton = this.wrapper.find('.btnReload');
        this.reloadCallback = null;

        this.reloadButton.on('click', () => {
            if (typeof this.reloadCallback === 'function') {
                this.reloadCallback();
            }
        });
    }

    /**
     * ==========================
     * Public Method
     * ==========================
     */

    onReload(callback) {
        this.reloadCallback = callback;
    }

    showLoading() {
        this.setState(DashboardChart.STATE.LOADING);
    }

    showChart() {
        this.setState(DashboardChart.STATE.CHART);
    }

    showEmpty() {
        this.clear();
        this.setState(DashboardChart.STATE.EMPTY);
    }

    showError() {
        this.setState(DashboardChart.STATE.ERROR);
    }

    /**
     * Update seluruh data chart
     */
    setData(chartData) {
        this.chart.data.labels = chartData.labels || [];
        this.chart.data.datasets = chartData.datasets || [];

        if (this.autoScaleY) {
            this.updateYAxis();
        }

        this.chart.update();
    }

    /**
     * Menghapus seluruh data chart
     */
    clear() {
        this.chart.data.labels = [];
        this.chart.data.datasets = [];

        this.chart.update();
    }

    destroy() {
        this.chart.destroy();
    }

    /**
     * ==========================
     * Private Method
     * ==========================
     */

    setState(state) {
        this.hideOverlay();

        const showCanvas =
            state === DashboardChart.STATE.CHART;

        this.canvas.css(
            'visibility',
            showCanvas ? 'visible' : 'hidden'
        );

        switch (state) {
            case DashboardChart.STATE.LOADING:
                this.loading.removeClass('d-none');

                break;
            case DashboardChart.STATE.EMPTY:
                this.empty.removeClass('d-none');

                break;
            case DashboardChart.STATE.ERROR:

                this.error.removeClass('d-none');
                break;
        }

    }

    hideOverlay() {
        this.loading.addClass('d-none');
        this.empty.addClass('d-none');
        this.error.addClass('d-none');
    }

    /**
     * Auto Scale Y Axis
     */
    updateYAxis() {
        if (
            !this.chart.options.scales ||
            !this.chart.options.scales.y
        ) {
            return;
        }

        const values = this.chart.data.datasets.flatMap(
            dataset => dataset.data
        );

        if (!values.length) {
            return;
        }

        const maxValue = Math.max(...values);
        const max = this.calculateMax(maxValue);

        this.chart.options.scales.y.max = max;
        this.chart.options.scales.y.ticks.stepSize =
            this.calculateStep(max);
    }

    /**
     * Memberikan ruang ±10%
     */
    calculateMax(value) {
        if (value <= 0) {
            return 10;
        }

        return Math.ceil(value * 1.1);
    }

    /**
     * Membuat step menjadi angka "cantik"
     */
    calculateStep(max) {
        const rawStep = max / 6;

        const magnitude = Math.pow(
            10,
            Math.floor(Math.log10(rawStep))
        );

        const normalized = rawStep / magnitude;
        let nice;

        if (normalized <= 1) {
            nice = 1;
        } else if (normalized <= 2) {
            nice = 2;
        } else if (normalized <= 5) {
            nice = 5;
        } else {
            nice = 10;
        }

        return nice * magnitude;
    }

}