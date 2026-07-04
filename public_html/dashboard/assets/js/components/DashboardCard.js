'use strict';

/**
 * Renders a row of KPI tiles. Each card is `{ label, value, unit }` —
 * `value === null` renders as an em dash, never a fabricated number
 * (decisions/IDR-012: several requested cards — Highest/Lowest/Average
 * Score, Difficulty — have no real figure in the current, frozen Dashboard
 * Data API and are expected to render this way today).
 */
export class DashboardCard {
    constructor(container) {
        this.container = container;
    }

    /** @param {{label: string, value: number|null, unit: string|null}[]} cards */
    render(cards) {
        this.container.textContent = '';

        cards.forEach((card) => {
            this.container.appendChild(this.buildTile(card));
        });
    }

    buildTile(card) {
        const col = document.createElement('div');
        col.className = 'col-md-3 col-sm-6 col-12';

        const tile = document.createElement('div');
        tile.className = 'card h-100 shadow-sm dashboard-card';

        const body = document.createElement('div');
        body.className = 'card-body';

        const label = document.createElement('div');
        label.className = 'text-muted small dashboard-card-label';
        label.textContent = card.label;

        const value = document.createElement('div');
        value.className = 'h4 mb-0 dashboard-card-value';
        value.textContent = formatCardValue(card.value, card.unit);

        body.appendChild(label);
        body.appendChild(value);
        tile.appendChild(body);
        col.appendChild(tile);

        return col;
    }
}

function formatCardValue(value, unit) {
    if (value === null || value === undefined) {
        return '—'; // em dash — "not available", never a fabricated 0 or blank.
    }

    if (unit === '%') {
        return (value * 100).toFixed(1) + '%';
    }

    if (typeof value === 'number') {
        return Number.isInteger(value) ? String(value) : value.toFixed(2);
    }

    return String(value);
}
