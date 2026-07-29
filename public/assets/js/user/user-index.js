function showLoadingShimmer() {
    document.getElementById('real-table-body')?.classList.add('d-none');
    document.getElementById('shimmer-table-body')?.classList.remove('d-none');
}

function hideLoadingShimmer() {
    document.getElementById('shimmer-table-body')?.classList.add('d-none');
    document.getElementById('real-table-body')?.classList.remove('d-none');
}

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById('filterUserForm');
    if (form) {
        form.addEventListener('submit', function () {
            showLoadingShimmer();
        });
    }

    const filterItems = document.querySelectorAll(".dropdown-menu a:not(.limit-item)");

    filterItems.forEach(item => {
        item.addEventListener("click", function (e) {
            e.preventDefault();

            const value = this.getAttribute("data-value");
            const text = this.querySelector("span") ? this.querySelector("span").textContent.trim() : this.textContent.trim();

            const dropdownContainer = this.closest(".dropdown");
            const buttonLabel = dropdownContainer?.querySelector("button span");

            if (value !== "" && buttonLabel) {
                buttonLabel.style.setProperty('color', '#12161C', 'important');
                buttonLabel.style.fontWeight = "600";
                buttonLabel.textContent = text;
            }

            dropdownContainer?.querySelectorAll('.custom-dropdown-item').forEach(el => {
                el.classList.remove('active');
                const icon = el.querySelector('svg');
                if (icon) icon.remove();
            });

            this.classList.add('active');
            this.insertAdjacentHTML('beforeend', '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#198754" class="bi bi-check-lg" viewBox="0 0 16 16"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg>');

            if (dropdownContainer?.id === "ctx-level") {
                const inputLevel = document.getElementById("input-level");
                if (inputLevel) inputLevel.value = value;
            } else if (dropdownContainer?.id === "ctx-role") {
                const inputRole = document.getElementById("input-role");
                if (inputRole) inputRole.value = value;
            } else if (dropdownContainer?.id === "ctx-status") {
                const inputStatus = document.getElementById("input-status");
                if (inputStatus) inputStatus.value = value;
            }
        });
    });

    const limitItems = document.querySelectorAll(".dropdown-menu a.limit-item");
    limitItems.forEach(item => {
        item.addEventListener("click", function (e) {
            e.preventDefault();
            const value = this.getAttribute("data-value");

            const labelLimit = document.getElementById("label-limit");
            const inputLimit = document.getElementById("input-limit");

            if (labelLimit) labelLimit.textContent = value;
            if (inputLimit) inputLimit.value = value;

            showLoadingShimmer();
            this.closest("form")?.submit();
        });
    });

    setTimeout(function () {
        hideLoadingShimmer();
    }, 300);
});