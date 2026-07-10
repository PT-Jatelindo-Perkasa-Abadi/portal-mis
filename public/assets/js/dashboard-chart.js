class DashboardChart {
    constructor(options) {
        this.canvas = $(options.canvas);
        this.wrapper = this.canvas.closest('.chart-card');
        this.chart = new Chart(
            this.canvas[0],
            {
                type: options.type,
                data: options.data,
                options: options.options
            }
        );

        this.loading = this.wrapper.find('.chart-loading');
        this.empty = this.wrapper.find('.chart-empty');
        this.error = this.wrapper.find('.chart-error');
        this.reloadButton = this.wrapper.find('.btnReload');
        this.reloadCallback = null;

        this.reloadButton.on('click', () => {
            if (this.reloadCallback) {
                this.reloadCallback();
            }
        });
    }

    onReload(callback) {
        this.reloadCallback = callback;
    }

    hideAll() {
        this.loading.addClass('d-none');
        this.empty.addClass('d-none');
        this.error.addClass('d-none');
    }

    showLoading() {
        this.hideAll();
        this.loading.removeClass('d-none');
    }

    showChart() {
        this.hideAll();
    }

    showEmpty() {
        this.hideAll();
        this.empty.removeClass('d-none');
    }

    showError() {
        this.hideAll();
        this.error.removeClass('d-none');
    }

    update(datasets, labels = null) {
        if (labels) {
            this.chart.data.labels = labels;
        }

        this.chart.data.datasets = datasets;
        this.chart.update();
    }

    destroy() {
        this.chart.destroy();
    }
}