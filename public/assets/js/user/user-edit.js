document.addEventListener("DOMContentLoaded", function () {
    // Level ID 2 = Mitra Acquirer (IT Provider)
    const ITP_LEVEL_ID = '2';

    const ctxItp = document.getElementById('ctx-itp');
    const inputItp = document.getElementById('input-itp');
    const labelItp = document.getElementById('label-itp');
    const itpError = document.getElementById('itp-error');
    const btnItp = document.getElementById('btn-itp');

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
            if (buttonLabel) buttonLabel.textContent = text;

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
                if (labelItp) labelItp.style.setProperty('color', '#12161C', 'important');
                hideItpError();
            }
        });
    });

    // Modal Initializations
    const elModalKonfirmasi = document.getElementById('modalKonfirmasiEdit');
    const elModalLoading = document.getElementById('modalLoadingEdit');
    const elModalSukses = document.getElementById('modalSuksesEdit');

    const modalKonfirmasi = elModalKonfirmasi ? new bootstrap.Modal(elModalKonfirmasi) : null;
    const modalLoading = elModalLoading ? new bootstrap.Modal(elModalLoading) : null;
    const modalSukses = elModalSukses ? new bootstrap.Modal(elModalSukses) : null;

    const formEditUser = document.getElementById('formEditUser');
    if (formEditUser) {
        formEditUser.addEventListener('submit', function (event) {
            event.preventDefault();

            const levelVal = document.getElementById('input-level')?.value || "";
            const roleVal = document.getElementById('input-role')?.value || "";

            if (levelVal === "" || roleVal === "") {
                alert("Mohon lengkapi pilihan Level User dan Role terlebih dahulu.");
                return false;
            }

            if (levelVal === ITP_LEVEL_ID && inputItp && inputItp.value === '') {
                showItpError();
                if (ctxItp) ctxItp.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            if (modalKonfirmasi) modalKonfirmasi.show();
        });
    }

    const btnProsesUpdate = document.getElementById('btnProsesUpdate');
    if (btnProsesUpdate) {
        btnProsesUpdate.addEventListener('click', function () {
            if (modalKonfirmasi) modalKonfirmasi.hide();
            if (modalLoading) modalLoading.show();

            const form = document.getElementById('formEditUser');
            if (!form) return;

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (modalLoading) modalLoading.hide();

                    if (data.success || data.code == 200) {
                        if (modalSukses) modalSukses.show();
                    } else {
                        alert("Gagal memperbarui data: " + data.msg);
                    }
                })
                .catch(error => {
                    if (modalLoading) modalLoading.hide();
                    console.error("Error Detail:", error.message);
                    alert("Terjadi kesalahan sistem:\n" + error.message.substring(0, 200));
                });
        });
    }

    const btnSelesaiEdit = document.getElementById('btnSelesaiEdit');
    if (btnSelesaiEdit) {
        btnSelesaiEdit.addEventListener('click', function () {
            if (modalSukses) modalSukses.hide();

            const redirectUrl = this.getAttribute('data-redirect-url') || "<?= $this->baseUrl('user'); ?>";
            window.location.href = redirectUrl;
        });
    }
});