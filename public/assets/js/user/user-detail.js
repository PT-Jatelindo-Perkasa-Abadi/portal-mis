document.addEventListener("DOMContentLoaded", function () {
    const elModalKonfirmasiReset = document.getElementById('modalKonfirmasiReset');
    const elModalStatus = document.getElementById('modalStatus');

    const modalKonfirmasiReset = elModalKonfirmasiReset ? new bootstrap.Modal(elModalKonfirmasiReset) : null;
    const modalStatus = elModalStatus ? new bootstrap.Modal(elModalStatus) : null;

    let isActionSuccess = false;

    const btnTriggerReset = document.getElementById('btnTriggerResetPassword');
    if (btnTriggerReset && modalKonfirmasiReset) {
        btnTriggerReset.addEventListener('click', function (e) {
            e.preventDefault();
            modalKonfirmasiReset.show();
        });
    }

    const btnProsesReset = document.getElementById('btnProsesResetPassword');
    if (btnProsesReset) {
        btnProsesReset.addEventListener('click', function () {
            if (modalKonfirmasiReset) modalKonfirmasiReset.hide();

            const loadingArea = document.getElementById('contentLoading');
            const responseArea = document.getElementById('contentResponse');

            if (loadingArea) {
                loadingArea.style.opacity = '1';
                loadingArea.classList.remove('d-none');
            }
            if (responseArea) {
                responseArea.classList.add('d-none');
                responseArea.style.opacity = '0';
            }

            if (modalStatus) modalStatus.show();

            const formData = new FormData();
            const roleText = document.getElementById('detail-role')?.value || '';
            const roleId = (roleText === 'Administrator') ? 1 : 2;

            formData.append('id', document.getElementById('detail-id')?.value || '');
            formData.append('email', document.getElementById('detail-email')?.value || '');
            formData.append('fullName', document.getElementById('detail-nama')?.value || '');
            formData.append('role', roleId);

            // Ambil URL & Assets dari data-attribute tombol trigger
            const resetUrl = btnTriggerReset?.getAttribute('data-reset-url') || '';
            const imgSuccess = btnTriggerReset?.getAttribute('data-img-success') || '';
            const imgError = btnTriggerReset?.getAttribute('data-img-error') || '';

            fetch(resetUrl, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (loadingArea) loadingArea.style.opacity = '0';

                    setTimeout(function () {
                        if (loadingArea) loadingArea.classList.add('d-none');

                        const statusImgEl = document.getElementById('statusImage');
                        const statusTitleEl = document.getElementById('statusTitle');
                        const statusMsgEl = document.getElementById('statusMessage');

                        if (data.success || data.code == 200) {
                            isActionSuccess = true;
                            if (statusImgEl) statusImgEl.src = imgSuccess;
                            if (statusTitleEl) statusTitleEl.textContent = 'Kata Sandi Berhasil Direset';
                            if (statusMsgEl) statusMsgEl.textContent = 'Kata sandi akun user telah berhasil direset.';
                        } else {
                            isActionSuccess = false;
                            if (statusImgEl) statusImgEl.src = imgError;
                            if (statusTitleEl) statusTitleEl.textContent = 'Kata Sandi Gagal Direset';
                            if (statusMsgEl) statusMsgEl.textContent = data.msg ? data.msg : "Terjadi kesalahan saat melakukan reset kata sandi user";
                        }

                        if (responseArea) {
                            responseArea.classList.remove('d-none');
                            setTimeout(function () {
                                responseArea.style.opacity = '1';
                            }, 50);
                        }
                    }, 200);
                })
                .catch(error => {
                    if (loadingArea) loadingArea.style.opacity = '0';
                    setTimeout(function () {
                        if (loadingArea) loadingArea.classList.add('d-none');
                        isActionSuccess = false;

                        const statusImgEl = document.getElementById('statusImage');
                        const statusTitleEl = document.getElementById('statusTitle');
                        const statusMsgEl = document.getElementById('statusMessage');

                        if (statusImgEl) statusImgEl.src = imgError;
                        if (statusTitleEl) statusTitleEl.textContent = 'Reset Gagal';
                        if (statusMsgEl) statusMsgEl.textContent = "Terjadi kendala koneksi ke server.";

                        if (responseArea) {
                            responseArea.classList.remove('d-none');
                            setTimeout(function () {
                                responseArea.style.opacity = '1';
                            }, 50);
                        }
                    }, 200);
                });
        });
    }

    const btnStatusOke = document.getElementById('btnStatusOke');
    if (btnStatusOke) {
        btnStatusOke.addEventListener('click', function () {
            if (modalStatus) modalStatus.hide();
            if (isActionSuccess) {
                window.location.reload();
            }
        });
    }
});