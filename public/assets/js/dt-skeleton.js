/**
 * 1774173 by @0x748...8651
 */

(function (window, $) {
    function renderShimmerRows(
        $targetTbody,
        columnCount,
        rowCount = 5,
        fixedLeft = 0,
        fixedRight = 0,
        columnWidths = []
    ) {
        $targetTbody.empty();

        let totalRightWidth = 0;

        for (let i = columnCount - fixedRight; i < columnCount; i++) {
            totalRightWidth += columnWidths[i] || 100;
        }

        for (let i = 0; i < rowCount; i++) {
            const $row = $('<tr></tr>');
            let leftOffset = 0;
            let rightOffset = totalRightWidth;

            for (let j = 0; j < columnCount; j++) {
                let tdClass = "";
                let style = "";
                const width = columnWidths[j] || 100;

                if (j < fixedLeft) {
                    tdClass = "fixed-left";
                    style =
                        `position: sticky; ` +
                        `left: ${leftOffset}px; ` +
                        `z-index: 2; ` +
                        `width: ${width}px;`;

                    leftOffset += width;
                } else if (j >= columnCount - fixedRight) {
                    rightOffset -= width;
                    tdClass = "fixed-right";
                    style =
                        `position: sticky; ` +
                        `right: ${rightOffset}px; ` +
                        `z-index: 2; ` +
                        `width: ${width}px;`;
                } else {
                    style = `width: ${width}px;`;
                }

                $row.append(
                    `<td class="${tdClass}" style="${style}">
                        <div class="skeleton-cell">&nbsp;</div>
                    </td>`
                );
            }

            $targetTbody.append($row);
        }
    }


    function getColumnWidthsFromThead($table) {
        const widths = [];

        $table.find("thead th").each(function () {
            const width = this.getBoundingClientRect().width;
            widths.push(Math.round(width));
        });

        return widths;
    }


    function destroyShimmer($table) {
        const $tbody = $table.find("tbody");

        $tbody.find("tr").each(function () {
            if ($(this).find(".skeleton-cell").length > 0) {
                $(this).remove();
            }
        });
    }


    const SkeletonLoader = {
        init: function (tableSelector, rowCount = 5) {
            const $table = $(tableSelector);

            if (!$table.length || !$table.DataTable) {
                console.warn(
                    "Table not found or DataTable not initialized yet."
                );
                return;
            }

            const dataTable = $table.DataTable();
            const settings = dataTable.settings()[0];
            const columns = settings.aoColumns || [];
            const columnCount = columns.length;
            const columnWidths =
                getColumnWidthsFromThead($table);
            const $mainTbody =
                $table.find("tbody");
            const fixedLeftColumns =
                settings.oInit.fixedColumns?.start || 0;
            const fixedRightColumns =
                settings.oInit.fixedColumns?.end || 0;

            renderShimmerRows(
                $mainTbody,
                columnCount,
                rowCount,
                fixedLeftColumns,
                fixedRightColumns,
                columnWidths
            );
        },
        destroy: function (tableSelector) {
            const $table = $(tableSelector);

            if (!$table.length || !$table.DataTable) {
                console.warn(
                    "Table not found or DataTable not initialized yet."
                );
                return;
            }
            destroyShimmer($table);
        }
    };


    // Buat tersedia secara global
    window.SkeletonLoader = SkeletonLoader;

})(window, jQuery);