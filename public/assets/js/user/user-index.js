function showLoadingShimmer() {
    document.getElementById('real-table-body')?.classList.add('d-none');
    document.getElementById('shimmer-table-body')?.classList.remove('d-none');
}

function hideLoadingShimmer() {
    document.getElementById('shimmer-table-body')?.classList.add('d-none');
    document.getElementById('real-table-body')?.classList.remove('d-none');
}

document.addEventListener("DOMContentLoaded", function () {

    const userListContent = document.getElementById('userListContent');
    const collapseArrow = document.getElementById('collapseArrow');

    if (userListContent && collapseArrow) {
        userListContent.addEventListener('hide.bs.collapse', function () {
            collapseArrow.classList.add('collapsed');
        });

        userListContent.addEventListener('show.bs.collapse', function () {
            collapseArrow.classList.remove('collapsed');
        });
    }

    const form = document.getElementById('filterUserForm');

    if (form) {
        form.addEventListener('submit', function () {
            showLoadingShimmer();
        });
    }

    // 🎯 EVENT LISTENER KLIK HEADER UNTUK SORTING MANUAL
    const sortableHeaders = document.querySelectorAll('.sortable-header');

    sortableHeaders.forEach(header => {
        header.addEventListener('click', function () {
            const sortField = this.dataset.sort;
            const inputSort = document.getElementById('input-sort');
            const inputDir = document.getElementById('input-dir');

            if (!inputSort || !inputDir) return;

            if (inputSort.value === sortField) {
                inputDir.value = inputDir.value === 'asc' ? 'desc' : 'asc';
            } else {
                inputSort.value = sortField;
                inputDir.value = 'asc';
            }

            showLoadingShimmer();
            document.getElementById('filterUserForm')?.submit();
        });
    });

    const filterItems = document.querySelectorAll(".dropdown-menu a:not(.limit-item)");

    filterItems.forEach(item => {

        item.addEventListener("click", function (e) {

            e.preventDefault();

            const value = this.dataset.value;
            const text = this.querySelector("span")
                ? this.querySelector("span").textContent.trim()
                : this.textContent.trim();

            const dropdownContainer = this.closest(".dropdown");
            const buttonLabel = dropdownContainer.querySelector("button span");

            if (value !== "") {
                buttonLabel.style.setProperty('color', '#12161C', 'important');
                buttonLabel.style.fontWeight = "600";
                buttonLabel.textContent = text;
            }

            dropdownContainer.querySelectorAll(".custom-dropdown-item").forEach(el => {
                el.classList.remove("active");
                el.querySelector("svg.bi-check-lg")?.remove();
            });

            this.classList.add("active");

            this.insertAdjacentHTML(
                "beforeend",
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#198754" class="bi bi-check-lg" viewBox="0 0 16 16"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg>'
            );

            switch (dropdownContainer.id) {
                case "ctx-level":
                    document.getElementById("input-level").value = value;
                    break;

                case "ctx-role":
                    document.getElementById("input-role").value = value;
                    break;

                case "ctx-status":
                    document.getElementById("input-status").value = value;
                    break;
            }

        });

    });

    const limitItems = document.querySelectorAll(".limit-item");

    limitItems.forEach(item => {

        item.addEventListener("click", function (e) {

            e.preventDefault();

            const value = this.dataset.value;

            document.getElementById("label-limit").textContent = value;
            document.getElementById("input-limit").value = value;

            showLoadingShimmer();

            this.closest("form").submit();

        });

    });

    setTimeout(hideLoadingShimmer, 300);

});