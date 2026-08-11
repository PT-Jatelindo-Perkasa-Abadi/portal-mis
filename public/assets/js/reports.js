$(function () {
    function formatOption(state) {
        if (!state.id) {
            return state.text;
        }

        let selected = $('#layanan').val();
        let check = '';

        if (selected === state.id) {
            check = '<span class="icon-checked-option"></span>';
        }

        return $(
            '<div style="display:flex;justify-content:space-between;align-items:center;width:100%;">' +
                '<span>' + state.text + '</span>' +
                check +
            '</div>'
        );
    }


    $('#layanan').select2({
        width: '100%',
        minimumResultsForSearch: -1,
        templateResult: formatOption,
        escapeMarkup: function(markup) {
            return markup;
        }
    });

    $('#listitp').select2({
        placeholder: "Pilih Mitra Acquirer",
        width: '100%',
        allowClear: true
    });

    $('#listmitra').select2({
        placeholder: "Pilih Sub Mitra Acquirer",
        width: '100%',
        allowClear: true
    });

    $('#btnSearch').on('click', function() {
        let length = parseInt($('#lengthData').val(), 10);

        $('#itpnow').DataTable().page.len(length);
        $('#itpnow').DataTable().ajax.reload(null, true);

        let tgl = $('#filterDateMitra').val();
        let it_provider = $('#listitp').find(':selected').text();
        let layanan = $('#layanan').find(':selected').text();
        let keyword = $('#keyword').val();

        if (tgl != "") {
            $('#badge-date-report').text('Tanggal ' + tgl);
        }

        if (it_provider != "") {
            $('#badge-itp-report').show();
            $('#badge-itp-report').text(it_provider?.trim());
        }

        if (layanan != "") {
            $('#badge-service-report').show();
            $('#badge-service-report').text(layanan?.trim());
        }

        if (keyword.length > 0) {
            $('#badge-search-report').show();
            $('#badge-search-report').text(keyword?.trim());
        } else {
            $('#badge-search-report').hide();
        }
    });

    $('#btnSearchMitra').on('click', function () {
        let length = parseInt($('#lengthData').val(), 10);

        $('#mitranow').DataTable().page.len(length);
        $('#mitranow').DataTable().ajax.reload(null, true);

        let tgl = $('#filterDateSubMitra').val();
        let it_provider = $('#listitp').find(':selected').text();
        let layanan = $('#layanan').find(':selected').text();
        let keyword = $('#keyword').val();
        let mitra = $('#listmitra').find(':selected').text();

        if (tgl != "") {
            $('#badge-date-report-mitra').text('Tanggal ' + tgl);
        }

        if (it_provider != "") {
            $('#badge-itp-report-mitra').show();
            $('#badge-itp-report-mitra').text(it_provider);
        }

        if (mitra != "") {
            $('#badge-p-report-mitra').show();
            $('#badge-p-report-mitra').text(mitra);
        }

        if (layanan != "") {
            $('#badge-service-report-mitra').show();
            $('#badge-service-report-mitra').text(layanan);
        }

        if (keyword != "") {
            $('#badge-search-report-mitra').show();
            $('#badge-search-report-mitra').text(keyword);
        } else {
            $('#badge-search-report-mitra').hide();
        }
    });

    $('#btnSearchRoleMitra').on('click', function () {
        let length = parseInt($('#lengthData').val(), 10);

        $('#rolemitranow').DataTable().page.len(length);
        $('#rolemitranow').DataTable().ajax.reload(null, true);

        let tgl = $('#filterDateItpMitra').val();
        let layanan = $('#layanan').find(':selected').text();
        let keyword = $('#keyword').val();
        let mitra = $('#listmitra').find(':selected').text();

        if (tgl != "") {
            $('#badge-date-report-mitra').text('Tanggal ' + tgl);
        }

        if (mitra != "") {
            $('#badge-p-report-mitra').show();
            $('#badge-p-report-mitra').text(mitra);
        }

        if (layanan != "") {
            $('#badge-service-report-mitra').show();
            $('#badge-service-report-mitra').text(layanan);
        }

        if (keyword != "") {
            $('#badge-search-report-mitra').show();
            $('#badge-search-report-mitra').text(keyword);
        } else {
            $('#badge-search-report-mitra').hide();
        }
    });

    $('#btnDownload').on('click', function () {
        let table = $('#itpnow').DataTable();
        let start = table.page.info().start;
        let length = table.page.info().length;
        let params = {
            tanggal: $('#filterDateMitra')
                .val()
                .replaceAll('/', '-')
                .split('-')
                .reverse()
                .join('-'),
            keyword: $('#keyword').val(),
            it_provider: $('#listitp').val(),
            layanan: $('#layanan').val(),
            mitra: "",
            isSubMitra: 0,
            start,
            length
        };

        let form = $('<form>', {
            method: 'POST',
            action: '/reports/index/download'
        });

        $.each(params, function (key, value) {
            $('<input>', {
                type: 'hidden',
                name: key,
                value: value == null ? '' : value
            }).appendTo(form);
        });

        $('body').append(form);
        form.submit();
        form.remove();
    });

    $('#btnDownloadmitra').on('click', function () {
        let table = $('#mitranow').DataTable();
        let start = table.page.info().start;
        let length = table.page.info().length;
        let params = {
            tanggal: $('#filterDateSubMitra')
                .val()
                .replaceAll('/', '-')
                .split('-')
                .reverse()
                .join('-'),
            keyword: $('#keyword').val(),
            it_provider: $('#listitp').val(),
            layanan: $('#layanan').val(),
            mitra: $('#listmitra').val(),
            isSubMitra: 1,
            start,
            length
        };

        let form = $('<form>', {
            method: 'POST',
            action: '/reports/index/download'
        });

        $.each(params, function (key, value) {
            $('<input>', {
                type: 'hidden',
                name: key,
                value: value == null ? '' : value
            }).appendTo(form);
        });

        $('body').append(form);
        form.submit();
        form.remove();
    });

    $('#btnDownloadItpmitra').on('click', function () {
        let table = $('#rolemitranow').DataTable();
        let start = table.page.info().start;
        let length = table.page.info().length;
        let params = {
            tanggal: $('#filterDateItpMitra')
                .val()
                .replaceAll('/', '-')
                .split('-')
                .reverse()
                .join('-'),
            keyword: $('#keyword').val(),
            it_provider: "",
            layanan: $('#layanan').val(),
            mitra: $('#listmitra').val(),
            isSubMitra: 2,
            start,
            length
        };

        let form = $('<form>', {
            method: 'POST',
            action: '/reports/index/downloaditpmitra'
        });

        $.each(params, function (key, value) {
            $('<input>', {
                type: 'hidden',
                name: key,
                value: value == null ? '' : value
            }).appendTo(form);
        });

        $('body').append(form);
        form.submit();
        form.remove();
    });

    $('.custom-tabs .nav-link').on('click', function(e) {
        e.preventDefault();

        $('.custom-tabs .nav-link').removeClass('active');
        $(this).addClass('active');

        let url = $(this).data('url');
        window.location.href = url;
    });

    $('#btn-report-reset, #btn-reportmitra-reset').click(function(){
        location.reload();
    });

    $('#listitp').on('select2:clear', function (e) {
        $('#badge-itp-report').attr('style','display:none !important');
        $('#badge-itp-report-mitra').attr('style','display:none !important');
    });

    $('#listmitra').on('select2:clear', function (e) {
        $('#badge-p-report-mitra').attr('style','display:none !important');
    });
});


$(document).ready(function() {
    function formatRupiah(angka) {
        if(angka == null || angka == '')
            return '0';

        return Number(angka).toLocaleString('id-ID');
    }

    $('#itpnow')
        .on('preXhr.dt', function(e, settings) {
            const table = $(this).DataTable();

            table.columns.adjust();
            SkeletonLoader.init('#itpnow', 6);
            $('#reportTransactionMitra .summary-value').html("<div class='skeleton-cell w-100'></div>");
        })
        .on('xhr.dt', function() {
            SkeletonLoader.destroy('#itpnow');
        });

    $('#itpnow').DataTable({
        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: 10,
        destroy: true,
        autoWidth: true,
        language: {
            processing: "",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            emptyTable:`
                <div class='d-flex flex-column align-items-center justify-content-center py-4'>
                    <img src='../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px; height: auto; object-fit: contain;' class='mb-3'>
                    <h5 class='fw-bold mb-1' style='color: #12161C; font-size: 16px; font-family: sans-serif;'>Data Transaksi Masih Kosong</h5>
                    <p class='m-0' style='color: #595C60; font-size: 14px; font-weight: 400;'>Saat ini data laporan transaksi Mitra Acquirer belum tersedia</p>
                </div>`
        },
        ajax: {
            url: '/reports/index/listtransaksi',
            type: 'POST',
            data: function(d) {
                d.tanggal     = $('#filterDateMitra')
                    .val()
                    .replaceAll('/', '-')
                    .split('-')
                    .reverse()
                    .join('-');
                d.it_provider = $('#listitp').val();
                d.layanan     = $('#layanan').val();
                d.keyword     = $('#keyword').val();
            },
            dataSrc: function(json) {
                let lembar = 0;
                let tagihan = 0;
                let admin = 0;
                let total = 0;

                $.each(json.data,function(i,row) {
                    lembar += parseInt(row.lembar || 0);
                    tagihan += parseFloat(row.sum_total_tagihan || 0);
                    admin += parseFloat(row.sum_total_fee || 0);
                    total += parseFloat(row.sum_total_nomial || 0);
                });

                $('#lblLembar').text(formatRupiah(lembar));
                $('#lblTagihan').text('Rp'+formatRupiah(tagihan));
                $('#lblAdmin').text('Rp'+formatRupiah(admin));
                $('#lblTotal').text('Rp'+formatRupiah(total));

                return json.data;
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: function (data, type, row, meta) {
                    return meta.settings._iDisplayStart + meta.row + 1;
                }
            },
            {
            data: 'nama_technical_provider',
                defaultContent: '-'
            },
            {
                data: 'product',
                defaultContent: '-',
                render: function(data) {
                    return `<div class="dt-status status-neutral fw-medium py-1">${data.toLowerCase()}</div>`;
                }
            },
            {
                data: 'lembar',
                defaultContent: '0'
            },
            {
                data: 'sum_total_tagihan',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_fee',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_nomial',
                render: function(data){
                    return formatRupiah(data);
                }
            }
        ]
    });


    $('#mitranow')
        .on('preXhr.dt', function(e, settings) {
            const table = $(this).DataTable();

            table.columns.adjust();
            SkeletonLoader.init('#mitranow', 6);
            $('#reportTransactionSubMitra .summary-value').html("<div class='skeleton-cell w-100'></div>");
        })
        .on('xhr.dt', function() {
            SkeletonLoader.destroy('#mitranow');
        });

    $('#mitranow').DataTable({
        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: 10,
        destroy: true,
        autoWidth: true,
        language: {
            processing: "",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            emptyTable:`
            <div class='d-flex flex-column align-items-center justify-content-center py-4'>
                <img src='../../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px; height: auto; object-fit: contain;' class='mb-3'>
                <h5 class='fw-bold mb-1' style='color: #12161C; font-size: 16px; font-family: sans-serif;'>Data Transaksi Masih Kosong</h5>
                <p class='m-0' style='color: #595C60; font-size: 14px; font-weight: 400;'>Saat ini data laporan transaksi Mitra belum tersedia </p>
            </div>`
        },
        ajax: {
            url: '/reports/index/listtransaksi',
            type: 'POST',
            data: function (d) {
                d.tanggal     = $('#filterDateSubMitra')
                    .val()
                    .replaceAll('/', '-')
                    .split('-')
                    .reverse()
                    .join('-');
                d.it_provider = $('#listitp').val();
                d.layanan     = $('#layanan').val();
                d.keyword     = $('#keyword').val();
                d.mitra       = $('#listmitra').val();
            },
            dataSrc: function (json) {
                let lembar = 0;
                let tagihan = 0;
                let admin = 0;
                let total = 0;

                $.each(json.data,function(i,row){
                    lembar += parseInt(row.lembar || 0);
                    tagihan += parseFloat(row.sum_total_tagihan || 0);
                    admin += parseFloat(row.sum_total_fee || 0);
                    total += parseFloat(row.sum_total_nomial || 0);
                });

                $('#lblLembarmitra').text(formatRupiah(lembar));
                $('#lblTagihanmitra').text('Rp'+formatRupiah(tagihan));
                $('#lblAdminmitra').text('Rp'+formatRupiah(admin));
                $('#lblTotalmitra').text('Rp'+formatRupiah(total));

                return json.data;
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: function (data, type, row, meta) {
                    return meta.settings._iDisplayStart + meta.row + 1;
                }
            },
            {
                data: 'nama_mitra',
                defaultContent: '-'
            },
            {
                data: 'nama_technical_provider',
                defaultContent: '-'
            },
            {
                data: 'product',
                defaultContent: '-',
                render: function(data) {
                    return `<div class="dt-status status-neutral fw-medium py-1">${data.toLowerCase()}</div>`;
                }
            },
            {
                data: 'lembar',
                defaultContent: '0'
            },
            {
                data: 'sum_total_tagihan',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_fee',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_nomial',
                render: function(data) {
                    return formatRupiah(data);
                }
            }
        ]
    });


    $('#rolemitranow')
        .on('preXhr.dt', function(e, settings) {
            const table = $(this).DataTable();

            table.columns.adjust();
            SkeletonLoader.init('#rolemitranow', 6);
            $('#reportTransactionSubMitra .summary-value').html("<div class='skeleton-cell w-100'></div>");
        })
        .on('xhr.dt', function() {
            SkeletonLoader.destroy('#rolemitranow');
        });

    $('#rolemitranow').DataTable({
        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: 10,
        destroy: true,
        autoWidth: true,
        language: {
            processing: "",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            emptyTable:`
                <div class='d-flex flex-column align-items-center justify-content-center py-4'>
                    <img src='../../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px; height: auto; object-fit: contain;' class='mb-3'>
                    <h5 class='fw-bold mb-1' style='color: #12161C; font-size: 16px; font-family: sans-serif;'>Data Transaksi Masih Kosong</h5>
                    <p class='m-0' style='color: #595C60; font-size: 14px; font-weight: 400;'>Saat ini data laporan transaksi Mitra belum tersedia </p>
                </div>`
        },
        ajax: {
            url: '/reports/index/listtransaksi',
            type: 'POST',
            data: function (d) {
                d.tanggal     = $('#filterDateItpMitra')
                    .val()
                    .replaceAll('/', '-')
                    .split('-')
                    .reverse()
                    .join('-');
                d.it_provider = $('#itpcode').val();
                d.layanan     = $('#layanan').val();
                d.keyword     = $('#keyword').val();
                d.mitra       = $('#listmitra').val();
            },
            dataSrc: function(json) {
                let lembar = 0;
                let tagihan = 0;
                let admin = 0;
                let total = 0;

                $.each(json.data,function(i,row) {
                    lembar += parseInt(row.lembar || 0);
                    tagihan += parseFloat(row.sum_total_tagihan || 0);
                    admin += parseFloat(row.sum_total_fee || 0);
                    total += parseFloat(row.sum_total_nomial || 0);
                });

                $('#lblLembarmitra').text(formatRupiah(lembar));
                $('#lblTagihanmitra').text('Rp'+formatRupiah(tagihan));
                $('#lblAdminmitra').text('Rp'+formatRupiah(admin));
                $('#lblTotalmitra').text('Rp'+formatRupiah(total));

                return json.data;
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: function (data, type, row, meta) {
                    return meta.settings._iDisplayStart + meta.row + 1;
                }
            },
            {
                data: 'nama_mitra',
                defaultContent: '-'
            },
            {
                data: 'product',
                defaultContent: '-',
                render: function(data) {
                    return `<div class="dt-status status-neutral fw-medium py-1">${data.toLowerCase()}</div>`;
                }
            },
            {
                data: 'lembar',
                defaultContent: '0'
            },
            {
                data: 'sum_total_tagihan',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_fee',
                render: function(data) {
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_nomial',
                render: function(data) {
                    return formatRupiah(data);
                }
            }
        ]
    });
});