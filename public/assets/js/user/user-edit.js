document.addEventListener("DOMContentLoaded", () => {

    const ITP_LEVEL_ID = "2";

    const ctxItp = document.getElementById("ctx-itp");
    const inputItp = document.getElementById("input-itp");
    const labelItp = document.getElementById("label-itp");
    const btnItp = document.getElementById("btn-itp");
    const itpError = document.getElementById("itp-error");

    const form = document.getElementById("formEditUser");

    const modalKonfirmasi = createModal("modalKonfirmasiEdit");
    const modalLoading = createModal("modalLoadingEdit");
    const modalSukses = createModal("modalSuksesEdit");

    initDropdown();
    initFormSubmit();
    initProcessButton();
    initSuccessButton();

    function createModal(id) {
        const element = document.getElementById(id);
        return element ? new bootstrap.Modal(element) : null;
    }

    function toggleItp(levelValue) {

        if (!ctxItp) return;

        if (levelValue === ITP_LEVEL_ID) {

            ctxItp.classList.remove("d-none");

        } else {

            ctxItp.classList.add("d-none");

            if (inputItp) inputItp.value = "";

            if (labelItp) {
                labelItp.textContent = "Pilih IT provider";
                labelItp.style.setProperty("color", "#B8B9BB", "important");
            }

            hideItpError();

        }

    }

    function showItpError() {

        itpError?.classList.remove("d-none");
        btnItp?.classList.add("border-danger");

    }

    function hideItpError() {

        itpError?.classList.add("d-none");
        btnItp?.classList.remove("border-danger");

    }

    function initDropdown() {

        const dropdownItems = document.querySelectorAll(".dropdown-menu a");

        dropdownItems.forEach(item => {

            item.addEventListener("click", function (e) {

                e.preventDefault();

                const value = this.dataset.value;
                const text = this.textContent.trim();

                const container = this.closest(".col-md-6");

                if (!container) return;

                const label = container.querySelector("button span");

                if (label) {
                    label.textContent = text;
                }

                switch (container.id) {

                    case "ctx-status":
                        setValue("input-status", value);
                        break;

                    case "ctx-level":
                        setValue("input-level", value);
                        toggleItp(value);
                        break;

                    case "ctx-role":
                        setValue("input-role", value);
                        break;

                    case "ctx-itp":

                        if (inputItp) inputItp.value = value;

                        if (labelItp) {
                            labelItp.style.setProperty(
                                "color",
                                "#12161C",
                                "important"
                            );
                        }

                        hideItpError();

                        break;

                }

            });

        });

    }

    function setValue(id, value) {

        const input = document.getElementById(id);

        if (input) {
            input.value = value;
        }

    }

    function validateForm() {

        const level =
            document.getElementById("input-level")?.value || "";

        const role =
            document.getElementById("input-role")?.value || "";

        if (!level || !role) {

            alert("Mohon lengkapi pilihan Level User dan Role terlebih dahulu.");

            return false;

        }

        if (
            level === ITP_LEVEL_ID &&
            inputItp &&
            inputItp.value === ""
        ) {

            showItpError();

            ctxItp?.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            return false;

        }

        return true;

    }

    function initFormSubmit() {

        if (!form) return;

        form.addEventListener("submit", e => {

            e.preventDefault();

            if (!validateForm()) return;

            modalKonfirmasi?.show();

        });

    }

    function initProcessButton() {

        const button = document.getElementById("btnProsesUpdate");

        if (!button) return;

        button.addEventListener("click", async () => {

            modalKonfirmasi?.hide();
            modalLoading?.show();

            try {

                const response = await fetch(form.action, {
                    method: "POST",
                    body: new FormData(form)
                });

                if (!response.ok) {

                    throw new Error(await response.text());

                }

                const data = await response.json();

                modalLoading?.hide();

                if (data.success || data.code == 200) {

                    modalSukses?.show();

                } else {

                    alert("Gagal memperbarui data : " + data.msg);

                }

            } catch (err) {

                modalLoading?.hide();

                console.error(err);

                alert(
                    "Terjadi kesalahan sistem.\n\n" +
                    err.message.substring(0, 200)
                );

            }

        });

    }

    function initSuccessButton() {

        const button = document.getElementById("btnSelesaiEdit");

        if (!button) return;

        button.addEventListener("click", () => {

            modalSukses?.hide();

            const url =
                button.dataset.redirectUrl || "/";

            window.location.href = url;

        });

    }

});