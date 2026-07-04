'use strict';

/**
 * Renders a plain, accessible HTML table — used both as its own "detail"
 * view and as the screen-reader-accessible equivalent of a chart (Phase 6:
 * a `<canvas>` chart has no text alternative on its own). Every cell is
 * built with textContent; no HTML string concatenation anywhere.
 */
export class DashboardTable {
    constructor(container) {
        this.container = container;
    }

    /**
     * @param {{key: string, label: string, format?: string}[]} columns
     * @param {Array<Object>} rows
     * @param {string} emptyMessage
     */
    render(columns, rows, emptyMessage) {
        this.container.textContent = '';

        const table = document.createElement('table');
        table.className = 'table table-sm table-hover mb-0';

        table.appendChild(this.buildHead(columns));
        table.appendChild(this.buildBody(columns, rows, emptyMessage || 'ไม่มีข้อมูล'));

        this.container.appendChild(table);
    }

    buildHead(columns) {
        const thead = document.createElement('thead');
        const row = document.createElement('tr');

        columns.forEach((column) => {
            const th = document.createElement('th');
            th.scope = 'col';
            th.textContent = column.label;
            row.appendChild(th);
        });

        thead.appendChild(row);

        return thead;
    }

    buildBody(columns, rows, emptyMessage) {
        const tbody = document.createElement('tbody');

        if (!rows || rows.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = columns.length;
            cell.className = 'text-muted text-center py-3';
            cell.textContent = emptyMessage;
            row.appendChild(cell);
            tbody.appendChild(row);

            return tbody;
        }

        rows.forEach((rowData) => {
            const row = document.createElement('tr');

            columns.forEach((column) => {
                const cell = document.createElement('td');
                cell.textContent = formatCell(rowData[column.key], column.format);
                row.appendChild(cell);
            });

            tbody.appendChild(row);
        });

        return tbody;
    }
}

function formatCell(value, format) {
    if (value === null || value === undefined) {
        return '—';
    }

    if (format === 'percent') {
        return (value * 100).toFixed(1) + '%';
    }

    if (typeof value === 'number' && !Number.isInteger(value)) {
        return value.toFixed(3);
    }

    return String(value);
}
