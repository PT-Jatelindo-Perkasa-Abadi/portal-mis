$(document).ready(function () {

    $('#leveluser').select2({
        placeholder: "Pilih Level User",
        width: '100%',
        allowClear: true
    });

    $('#leveluser').on('select2:open', function () {
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


    $('#roleuser').select2({
        placeholder: "Pilih Role",
        width: '100%',
        allowClear: true
    });

    $('#roleuser').on('select2:open', function () {
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

    $('#btnSearchActivity').on('click', function () {

        $('#activitynow').DataTable().ajax.reload(null, true);
        var tgl = $('.custom-date-input-with-icon').val();
        var leveluser = $('#leveluser').find(':selected').text();
        var roleuser = $('#roleuser').find(':selected').text();
        var keyword = $('#keyword').val();

        if (tgl != "") {
            var arr = tgl.split('-');
            var tanggalFormat = arr[2] + '/' + arr[1] + '/' + arr[0];
            $('#badge-date-report').text('Tanggal ' + tanggalFormat);
        }

        if (leveluser != "") {
            $('#badge-level-activity').show();
            $('#badge-level-activity').text(leveluser);
        }

        if (roleuser != "") {
            $('#badge-role-activity').show();
            $('#badge-role-activity').text(roleuser);
        }

    });


    $('#activitynow').DataTable({
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        responsive: false,
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
            emptyTable: "<div class='d-flex flex-column align-items-center justify-content-center py-4'><img src='../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px; height: auto; object-fit: contain;' class='mb-3'><h5 class='fw-bold mb-1' style='color: #12161C; font-size: 16px; font-family: sans-serif;'>Data Aktivitas Masih Kosong</h5><p class='m-0' style='color: #595C60; font-size: 14px; font-weight: 400;'>Saat ini data log aktivitas belum tersedia </p></div>"
        },

        ajax: {
            url: '/activity/index/list',
            type: 'POST',
            data: function (d) {

                d.tanggal = $('.custom-date-input-with-icon').val();
                d.leveluser = $('#leveluser').val();
                d.roleuser = $('#roleuser').val();
                d.keyword = $('#keyword').val();

            }

        },
        drawCallback: function () {

            var api = this.api();

            if (api.page.info().recordsDisplay == 0) {

                $('.dataTables_bottom').hide();

            } else {

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
                data: 'created_at',
                defaultContent: '-',
                className: 'c-12',
            },
            {
                data: 'full_name',
                className: 'c-12',
            },
            {
                data: 'email',
                className: 'c-12',
            },
            {
                data: 'level_name',
                className: 'c-12',
                render: function (data, type, row) {
                    if (!data) return '-';

                    var lvl = data.toString().trim().toLowerCase();

                    if (lvl === 'admin mis' || lvl === 'mis' || lvl === 'asp') {
                        return 'ASP';
                    } else if (lvl === 'it provider' || lvl === 'mitra acquirer') {
                        return 'Mitra Acquirer';
                    }

                    return data;
                }

            },
            {
                data: 'role_name',
                className: 'c-12',

            },
            {
                data: 'menu',
                className: 'c-12',

            },
            {
                data: 'browser',
                className: 'c-12',

            },
            {
                data: 'deskripsi',
                className: 'c-12',

            }
        ]



    });



});