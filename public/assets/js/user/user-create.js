document.addEventListener("DOMContentLoaded", function () {
    const elModalKonfirmasi = document.getElementById('modalKonfirmasi');
    const elModalStatus = document.getElementById('modalStatus');

    const modalKonfirmasi = elModalKonfirmasi ? new bootstrap.Modal(elModalKonfirmasi) : null;
    const modalStatus = elModalStatus ? new bootstrap.Modal(elModalStatus) : null;

    let isActionSuccess = false;

    const ITP_LEVEL_ID = '2';

    const ctxItp = document.getElementById('ctx-itp');
    const inputItp = document.getElementById('input-itp');
    const labelItp = document.getElementById('label-itp');
    const itpError = document.getElementById('itp-error');
    const btnItp = document.getElementById('btn-itp');

    const formTambahUser = document.getElementById('formTambahUser');

    function toggleItp(levelValue) {
        if (!ctxItp) return;

        if (levelValue === ITP_LEVEL_ID) {
            ctxItp.classList.remove('d-none');
        } else {
            ctxItp.classList.add('d-none');
            if (inputItp) inputItp.value = '';
            if (labelItp) {
                labelItp.textContent = 'Pilih IT provider';
                labelItp.style.setProperty('color', '#B8B9BB', 'important');
            }
            hideItpError();
        }
    }

    function showItpError() {
        if (itpError) itpError.classList.remove('d-none');
        if (btnItp) btnItp.classList.add('border-danger');
    }

    function hideItpError() {
        if (itpError) itpError.classList.add('d-none');
        if (btnItp) btnItp.classList.remove('border-danger');
    }

    const dropdownItems = document.querySelectorAll(".dropdown-menu a");
    dropdownItems.forEach(item => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const value = this.getAttribute("data-value");
            const text = this.textContent;
            const dropdownContainer = this.closest(".col-md-6");
            if (!dropdownContainer) return;

            const buttonLabel = dropdownContainer.querySelector("button span");
            if (buttonLabel) {
                buttonLabel.textContent = text;
                buttonLabel.style.setProperty('color', '#12161C', 'important');
            }

            if (dropdownContainer.id === "ctx-status") {
                const inputStatus = document.getElementById("input-status");
                if (inputStatus) inputStatus.value = value;
            } else if (dropdownContainer.id === "ctx-level") {
                const inputLevel = document.getElementById("input-level");
                if (inputLevel) inputLevel.value = value;
                toggleItp(value);
            } else if (dropdownContainer.id === "ctx-role") {
                const inputRole = document.getElementById("input-role");
                if (inputRole) inputRole.value = value;
            } else if (dropdownContainer.id === "ctx-itp") {
                if (inputItp) inputItp.value = value;
                hideItpError();
            }
        });
    });

    if (formTambahUser) {
        formTambahUser.addEventListener('submit', function (event) {
            event.preventDefault();

            const inputLevel = document.getElementById('input-level');

            if (inputLevel && inputLevel.value === ITP_LEVEL_ID && inputItp && inputItp.value === '') {
                showItpError();
                if (ctxItp) ctxItp.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            if (modalKonfirmasi) modalKonfirmasi.show();
        });
    }

    const btnProsesSubmit = document.getElementById('btnProsesSubmit');
    if (btnProsesSubmit) {
        btnProsesSubmit.addEventListener('click', function () {
            if (modalKonfirmasi) modalKonfirmasi.hide();

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
            formData.append('status', document.getElementById('input-status')?.value || '1');
            formData.append('level_user', document.getElementById('input-level')?.value || '1');
            formData.append('role', document.getElementById('input-role')?.value || '1');
            formData.append('itp_code', inputItp?.value || '');
            formData.append('email', document.getElementById('email')?.value || '');
            formData.append('fullName', document.getElementById('fullName')?.value || '');

            const saveUrl = formTambahUser?.getAttribute('action') || '';
            const imgSuccess = formTambahUser?.getAttribute('data-img-success') || '';
            const imgFailed = formTambahUser?.getAttribute('data-img-failed') || '';

            fetch(saveUrl, {
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
                            if (statusTitleEl) statusTitleEl.textContent = 'Berhasil Ditambahkan';
                            if (statusMsgEl) statusMsgEl.textContent = 'Data user telah berhasil ditambahkan';
                        } else {
                            isActionSuccess = false;
                            if (statusImgEl) statusImgEl.src = imgFailed;
                            if (statusTitleEl) statusTitleEl.textContent = 'Gagal Ditambahkan';
                            if (statusMsgEl) statusMsgEl.textContent = data.msg ? data.msg : "Terjadi kesalahan saat menambahkan user";
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

                        if (statusImgEl) statusImgEl.src = imgFailed;
                        if (statusTitleEl) statusTitleEl.textContent = 'Gagal Ditambahkan';
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
                const redirectUrl = formTambahUser?.getAttribute('data-redirect-url');
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            }
        });
    }
});