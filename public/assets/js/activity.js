$(document).ready(function () {

    $.fn.dataTable.ext.errMode = 'throw';

    const activityContent = document.getElementById('activityContent');
    const collapseArrow = document.getElementById('collapseArrow');

    if (activityContent && collapseArrow) {

        activityContent.addEventListener('hide.bs.collapse', function () {
            collapseArrow.classList.add('collapsed');
        });

        activityContent.addEventListener('show.bs.collapse', function () {
            collapseArrow.classList.remove('collapsed');
        });

    }

    function renderTableShimmer() {
        var shimmerRows = '';
        var currentLimit = parseInt($('#lengthData').val(), 10) || 10;

        var totalShimmer = Math.min(currentLimit, 10);

        for (var i = 0; i < totalShimmer; i++) {
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

    $('#leveluser').select2({ placeholder: "Pilih Level User", width: '100%', allowClear: true });
    $('#roleuser').select2({ placeholder: "Pilih Role", width: '100%', allowClear: true });

    function updateBadges() {
        let dateVal = $('.custom-date-input-with-icon').val();
        if (dateVal) {
            let parts = dateVal.split('-');
            if (parts.length === 3) {
                $('#badge-date-report').text('Tanggal ' + parts[2] + '/' + parts[1] + '/' + parts[0]).show();
            }
        } else {
            $('#badge-date-report').hide();
        }

        let levelVal = $('#leveluser').val();
        let levelText = $('#leveluser option:selected').text().trim();
        if (levelVal && levelVal !== '') {
            $('#badge-level-activity')
                .text(levelText)
                .attr('style', 'height: 36px; background: #EBF2FE; color: #12161C; font-size: 14px; font-weight: 600; display: flex !important;');
        } else {
            $('#badge-level-activity').attr('style', 'display: none !important;');
        }

        let roleVal = $('#roleuser').val();
        let roleText = $('#roleuser option:selected').text().trim();
        if (roleVal && roleVal !== '') {
            $('#badge-role-activity')
                .text(roleText)
                .attr('style', 'height: 36px; background: #EBF2FE; color: #12161C; font-size: 14px; font-weight: 600; display: flex !important;');
        } else {
            $('#badge-role-activity').attr('style', 'display: none !important;');
        }
    }

    $('#btn-report-reset').on('click', function (e) {
        e.preventDefault();

        var today = new Date().toISOString().split('T')[0];
        $('.custom-date-input-with-icon').val(today);
        $('#leveluser').val(null).trigger('change.select2');
        $('#roleuser').val(null).trigger('change.select2');
        $('#keyword').val('');

        updateBadges();

        renderTableShimmer();
        table.ajax.reload(null, true);
    });

    $('#btnSearchActivity').on('click', function () {
        let length = parseInt($('#lengthData').val(), 10) || 10;

        updateBadges();
        renderTableShimmer();
        table.page.len(length);
        table.ajax.reload(null, true);
    });

    var table = $('#activitynow').DataTable({
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        responsive: false,
        processing: false,
        serverSide: true,
        searching: false,
        ordering: true,
        lengthChange: false,
        pageLength: parseInt($('#lengthData').val(), 10) || 10,
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
                d.length = $('#lengthData').val();
            }
        },

        drawCallback: function () {
            var api = this.api();
            if (api.page.info().recordsDisplay == 0) {
                $('.dataTables_bottom').hide();
            } else {
                $('.dataTables_bottom').show();
            }

            api.columns.adjust();
        },

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
                render: function (data, type, row) {
                    if (!data) return '-';
                    return data.charAt(0).toUpperCase() + data.slice(1);
                }
            },
            {
                data: 'menu',
                defaultContent: '-',
                width: '160px',
                className: 'c-12',
                render: function (data, type, row) {
                    if (!data) return '-';
                    return data.charAt(0).toUpperCase() + data.slice(1);
                }
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
                width: '410px',
                className: 'c-12 col-deskripsi',
            }
        ]
    });

    $('#lengthData').on('change', function () {
        var newLength = parseInt($(this).val(), 10) || 10;
        renderTableShimmer();
        table.page.len(newLength).draw();
    });

    $(window).on('resize', function () {
        table.columns.adjust();
    });

    $('#keyword').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnSearchActivity').click();
            return false;
        }
    });

});