$(document).ready(function () {

    // 1. Matikan Popup Alert Warning DataTables
    $.fn.dataTable.ext.errMode = 'throw';

    // 2. Helper Shimmer Loading (Skeleton 6 Baris Capsule)
    function renderTableShimmer() {
        var shimmerRows = '';
        for (var i = 0; i < 6; i++) {
            shimmerRows += `
                <tr style="height: 64px;">
                    <td class="text-center"><span class="skeleton-box sk-w-30"></span></td>
                    <td><span class="skeleton-box sk-w-80"></span></td>
                    <td><span class="skeleton-box sk-w-80"></span></td>
                    <td><span class="skeleton-box sk-w-100"></span></td>
                    <td><span class="skeleton-box sk-w-60"></span></td>
                    <td><span class="skeleton-box sk-w-60"></span></td>
                    <td><span class="skeleton-box sk-w-80"></span></td>
                    <td><span class="skeleton-box sk-w-60"></span></td>
                    <td><span class="skeleton-box sk-w-100"></span></td>
                </tr>`;
        }
        $('#activitynow tbody').html(shimmerRows);
    }

    // 3. Inisialisasi Select2
    $('#leveluser').select2({ placeholder: "Pilih Level User", width: '100%', allowClear: true });
    $('#roleuser').select2({ placeholder: "Pilih Role", width: '100%', allowClear: true });

    // 4. Button Search Handler
    $('#btnSearchActivity').on('click', function () {
        renderTableShimmer();
        $('#activitynow').DataTable().ajax.reload(null, true);
    });

    // 5. Inisialisasi DataTables dengan Ukuran Kolom Terkunci
    var table = $('#activitynow').DataTable({
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false, // Wajib false agar menggunakan width dari JS
        responsive: false,
        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: 10,
        destroy: true,

        language: {
            processing: "",
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Data tidak ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            emptyTable: "<div class='d-flex flex-column align-items-center justify-content-center py-4'><img src='../assets/img/img-data-empty.svg' alt='Data Kosong' style='width: 280px;' class='mb-3'><h5 class='fw-bold mb-1'>Data Aktivitas Masih Kosong</h5><p class='m-0'>Saat ini data log aktivitas belum tersedia</p></div>"
        },

        ajax: {
            url: '/activity/index/list',
            type: 'POST',
            beforeSend: function () {
                renderTableShimmer();
            },
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
            // Force re-align header dan body setiap selesai render
            api.columns.adjust();
        },

        // Diberikan width spesifik pada setiap kolom agar LURUS SEJAJAR 100%
        columns: [
            {
                data: null,
                orderable: false,
                defaultContent: '-',
                width: '60px',
                className: 'text-center c-12',
                render: function (data, type, row, meta) {
                    return meta.settings._iDisplayStart + meta.row + 1;
                }
            },
            {
                data: 'created_at',
                defaultContent: '-',
                width: '180px',
                className: 'c-12',
            },
            {
                data: 'full_name',
                defaultContent: '-',
                width: '180px',
                className: 'c-12',
            },
            {
                data: 'email',
                defaultContent: '-',
                width: '250px',
                className: 'c-12',
            },
            {
                data: 'level_name',
                defaultContent: '-',
                width: '140px',
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
                defaultContent: '-',
                width: '160px',
                className: 'c-12',
            },
            {
                data: 'menu',
                defaultContent: '-',
                width: '160px',
                className: 'c-12',
            },
            {
                data: 'browser',
                defaultContent: '-',
                width: '180px',
                className: 'c-12',
            },
            {
                data: 'deskripsi',
                defaultContent: '-',
                width: '300px',
                className: 'c-12',
            }
        ]
    });

    // Sesuaikan kembali kolom ketika ukuran jendela browser berubah
    $(window).on('resize', function () {
        table.columns.adjust();
    });

});