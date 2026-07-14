class ChartCard{
    constructor(canvasId) {
        this.canvas=$(canvasId);
        this.wrapper=this.canvas.parent();
        this.loading=this.wrapper.find('.chart-loading');
        this.empty=this.wrapper.find('.chart-empty');
        this.error=this.wrapper.find('.chart-error');
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

    showError() {
        this.hideAll();
        this.error.removeClass('d-none');
    }

    showEmpty() {
        this.hideAll();
        this.empty.removeClass('d-none');
    }

    showChart() {
        this.hideAll();
    }
}