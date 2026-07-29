$(function () {

    function formatOption(state) {

        if (!state.id) {
            return state.text;
        }

        var selected = $('#layanan').val();

        var check = '';

        if (selected === state.id) {
            check = '<span style="color:#22C55E;font-size:18px;">&#10003;</span>';
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
        minimumResultsForSearch: Infinity,
        templateResult: formatOption,
        escapeMarkup: function(markup){
            return markup;
        }
    });


    $('#layanan').on('select2:open', function () {

        $('.select2-dropdown').css({
            width: '300px',
            minWidth: '300px'
        });

    });

    $('#listitp').select2({
        placeholder: "Pilih IT Provider",
        width: '100%',
        allowClear: true
    });

    $('#listitp').on('select2:open', function () {
        $('.select2-dropdown').css({
            'width': '300px',
            'min-width': '300px'
        });

        $('.select2-container--open .select2-search__field').css({
            'width': '100%',
            'minWidth': '250px',
            'boxSizing': 'border-box'
        });
    });

    $('#listmitra').select2({
        placeholder: "Pilih Mitra",
        width: '100%',
        allowClear: true
    });

    $('#listmitra').on('select2:open', function () {

        $('.select2-dropdown').css({
            width: '300px',
            minWidth: '300px'
        });

    });

    $('#btnSearch').on('click', function () {

        $('#itpnow').DataTable().ajax.reload(null, true);
        var tgl = $('.custom-date-input-with-icon').val();
        var it_provider = $('#listitp').find(':selected').text();
        var layanan     = $('#layanan').find(':selected').text();
        var keyword     = $('#keyword').val();

        if (tgl != "") {
            var arr = tgl.split('-');
            var tanggalFormat = arr[2] + '/' + arr[1] + '/' + arr[0];
            $('#badge-date-report').text('Tanggal ' + tanggalFormat);
        }

        if(it_provider != "")
        {
            $('#badge-itp-report').show();
            $('#badge-itp-report').text(it_provider);
        }

        if(layanan != "")
        {
            $('#badge-service-report').show();
            $('#badge-service-report').text(layanan);
        }

        if(keyword != "")
        {
            $('#badge-search-report').show();
            $('#badge-search-report').text(keyword);
        }

    });


    $('#btnSearchMitra').on('click', function () {

        $('#mitranow').DataTable().ajax.reload(null, true);
        var tgl = $('.custom-date-input-with-icon').val();
        var it_provider = $('#listitp').find(':selected').text();
        var layanan     = $('#layanan').find(':selected').text();
        var keyword     = $('#keyword').val();
        var mitra       = $('#listmitra').find(':selected').text();

        if (tgl != "") {
            var arr = tgl.split('-');
            var tanggalFormat = arr[2] + '/' + arr[1] + '/' + arr[0];
            $('#badge-date-report-mitra').text('Tanggal ' + tanggalFormat);
        }

        if(it_provider != "")
        {
            $('#badge-itp-report-mitra').show();
            $('#badge-itp-report-mitra').text(it_provider);
        }

        if(mitra != "")
        {
            $('#badge-p-report-mitra').show();
            $('#badge-p-report-mitra').text(mitra);
        }

        if(layanan != "")
        {
            $('#badge-service-report-mitra').show();
            $('#badge-service-report-mitra').text(layanan);
        }

        if(keyword != "")
        {
            $('#badge-search-report-mitra').show();
            $('#badge-search-report-mitra').text(keyword);
        }

    });

    function downloadExcel(){

        tanggal     = $('.custom-date-input-with-icon').val();
        it_provider = $('#listitp').val();
        layanan     = $('#layanan').val();
        keyword     = $('#keyword').val();
        productCode = "";


        if (layanan === 'PREPAID') {
            productCode = "502";
        } else if (layanan === 'POSTPAID') {
            productCode = "501";
        } else if (layanan === 'NON-TAGLIST') {
            productCode = "504";
        }

        window.location =
            '/reports/index/download/tanggal/'+tanggal+'/it_provider/'+it_provider+'/layanan/'+productCode+'/keyword/'+keyword;

    }

    function downloadExcelMitra(){

        tanggal     = $('.custom-date-input-with-icon').val();
        it_provider = $('#listitp').val();
        layanan     = $('#layanan').val();
        keyword     = $('#keyword').val();
        mitra       = $('#listmitra').val();
        productCode = "";


        if (layanan === 'PREPAID') {
            productCode = "502";
        } else if (layanan === 'POSTPAID') {
            productCode = "501";
        } else if (layanan === 'NON-TAGLIST') {
            productCode = "504";
        }

        window.location =
            '/reports/index/downloadmitra/tanggal/'+tanggal+'/it_provider/'+it_provider+'/mitra/'+mitra+'/layanan/'+productCode+'/keyword/'+keyword;

    }

    function downloadExcelItpMitra(){

        tanggal     = $('.custom-date-input-with-icon').val();
        it_provider = $('#itpcode').val();
        layanan     = $('#layanan').val();
        keyword     = $('#keyword').val();
        mitra       = $('#listmitra').val();
        productCode = "";


        if (layanan === 'PREPAID') {
            productCode = "502";
        } else if (layanan === 'POSTPAID') {
            productCode = "501";
        } else if (layanan === 'NON-TAGLIST') {
            productCode = "504";
        }

        window.location =
            '/reports/index/downloaditpmitra/tanggal/'+tanggal+'/it_provider/'+it_provider+'/mitra/'+mitra+'/layanan/'+productCode+'/keyword/'+keyword;

    }

    $('#btnDownload').click(function(){
        downloadExcel();
    });

    $('#btnDownloadmitra').click(function(){
        downloadExcelMitra();
    });

    $('#btnDownloadItpmitra').click(function(){
        downloadExcelItpMitra();
    });


    $('.custom-tabs .nav-link').on('click', function (e) {

        e.preventDefault();

        $('.custom-tabs .nav-link').removeClass('active');

        $(this).addClass('active');

        var url = $(this).data('url');

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


$(document).ready(function () {


    function formatRupiah(angka){

        if(angka == null || angka == '')
            return '0';

        return Number(angka).toLocaleString('id-ID');

    }

    $('#itpnow').DataTable({

        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: 10,
        destroy: true,

        language: {
            processing: "Memuat data...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            search: "Cari:",
            emptyTable:"<div class='d-flex flex-column align-items-center justify-content-center py-4'><img src='../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px; height: auto; object-fit: contain;' class='mb-3'><h5 class='fw-bold mb-1' style='color: #12161C; font-size: 16px; font-family: sans-serif;'>Data Transaksi Masih Kosong</h5><p class='m-0' style='color: #595C60; font-size: 14px; font-weight: 400;'>Saat ini data laporan transaksi IT Provider belum tersedia </p></div>"
        },

        ajax: {
            url: '/reports/index/listtransaksi',
            type: 'POST',

            data: function (d) {

                d.tanggal     = $('.custom-date-input-with-icon').val();
                d.it_provider = $('#listitp').val();
                d.layanan     = $('#layanan').val();
                d.keyword     = $('#keyword').val();

            },
            dataSrc: function (json) {

                var lembar = 0;
                var tagihan = 0;
                var admin = 0;
                var total = 0;

                $.each(json.data,function(i,row){

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

        drawCallback: function(){

                var api = this.api();

                if(api.page.info().recordsDisplay == 0){

                    $('.dataTables_bottom').hide();

                }else{

                    $('.dataTables_bottom').show();
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
                defaultContent: '-'
            },
            {
                data: 'lembar',
                className: 'text-end',
                defaultContent: '0'
            },
            {
                data: 'sum_total_tagihan',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_fee',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_nomial',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            }
        ]

    });


    $('#mitranow').DataTable({

        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: 10,
        destroy: true,

        language: {
            processing: "Memuat data...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            search: "Cari:",
            emptyTable:"<div class='d-flex flex-column align-items-center justify-content-center py-4'><img src='../../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px; height: auto; object-fit: contain;' class='mb-3'><h5 class='fw-bold mb-1' style='color: #12161C; font-size: 16px; font-family: sans-serif;'>Data Transaksi Masih Kosong</h5><p class='m-0' style='color: #595C60; font-size: 14px; font-weight: 400;'>Saat ini data laporan transaksi Mitra belum tersedia </p></div>"
        },

        ajax: {
            url: '/reports/index/listtransaksi',
            type: 'POST',

            data: function (d) {

                d.tanggal     = $('.custom-date-input-with-icon').val();
                d.it_provider = $('#listitp').val();
                d.layanan     = $('#layanan').val();
                d.keyword     = $('#keyword').val();
                d.mitra       = $('#listmitra').val();

            },
            dataSrc: function (json) {

                var lembar = 0;
                var tagihan = 0;
                var admin = 0;
                var total = 0;

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

        drawCallback: function(){

                var api = this.api();

                if(api.page.info().recordsDisplay == 0){

                    $('.dataTables_bottom').hide();

                }else{

                    $('.dataTables_bottom').show();
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
                defaultContent: '-'
            },
            {
                data: 'lembar',
                className: 'text-end',
                defaultContent: '0'
            },
            {
                data: 'sum_total_tagihan',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_fee',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_nomial',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            }
        ]

    });


    
    $('#rolemitranow').DataTable({

        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: 10,
        destroy: true,

        language: {
            processing: "Memuat data...",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ total data)",
            search: "Cari:",
            emptyTable:"<div class='d-flex flex-column align-items-center justify-content-center py-4'><img src='../../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px; height: auto; object-fit: contain;' class='mb-3'><h5 class='fw-bold mb-1' style='color: #12161C; font-size: 16px; font-family: sans-serif;'>Data Transaksi Masih Kosong</h5><p class='m-0' style='color: #595C60; font-size: 14px; font-weight: 400;'>Saat ini data laporan transaksi Mitra belum tersedia </p></div>"
        },

        ajax: {
            url: '/reports/index/listtransaksi',
            type: 'POST',

            data: function (d) {

                d.tanggal     = $('.custom-date-input-with-icon').val();
                d.it_provider = $('#itpcode').val();
                d.layanan     = $('#layanan').val();
                d.keyword     = $('#keyword').val();
                d.mitra       = $('#listmitra').val();

            },
            dataSrc: function (json) {

                var lembar = 0;
                var tagihan = 0;
                var admin = 0;
                var total = 0;

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

        drawCallback: function(){

                var api = this.api();

                if(api.page.info().recordsDisplay == 0){

                    $('.dataTables_bottom').hide();

                }else{

                    $('.dataTables_bottom').show();
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
                defaultContent: '-'
            },
            {
                data: 'lembar',
                className: 'text-end',
                defaultContent: '0'
            },
            {
                data: 'sum_total_tagihan',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_fee',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            },
            {
                data: 'sum_total_nomial',
                className: 'text-end',
                render: function(data){
                    return formatRupiah(data);
                }
            }
        ]

    });


    $('#btnSearchRoleMitra').on('click', function () {

        $('#rolemitranow').DataTable().ajax.reload(null, true);
        var tgl = $('.custom-date-input-with-icon').val();
        var itpprovicer = $('#itpcode').val();
        var layanan     = $('#layanan').find(':selected').text();
        var keyword     = $('#keyword').val();
        var mitra       = $('#listmitra').find(':selected').text();

        if (tgl != "") {
            var arr = tgl.split('-');
            var tanggalFormat = arr[2] + '/' + arr[1] + '/' + arr[0];
            $('#badge-date-report-mitra').text('Tanggal ' + tanggalFormat);
        }

        if(mitra != "")
        {
            $('#badge-p-report-mitra').show();
            $('#badge-p-report-mitra').text(mitra);
        }

        if(layanan != "")
        {
            $('#badge-service-report-mitra').show();
            $('#badge-service-report-mitra').text(layanan);
        }

        if(keyword != "")
        {
            $('#badge-search-report-mitra').show();
            $('#badge-search-report-mitra').text(keyword);
        }

    });


});