'use strict';

/**
 * Thin Chart.js wrapper. Assembles only `data`/`options` from whatever the
 * Dashboard Data API already returned — never computes a percentage, a
 * threshold, or a color rule itself (decisions/IDR-002's server-assembled
 * configuration principle: business rules belong in PHP, this is a renderer).
 * Reuses one Chart instance across refreshes (`.update()`) rather than
 * destroying and recreating it, per Phase 7 (Performance: chart update
 * without reload).
 */
export class DashboardChart {
    constructor(canvas, type) {
        this.canvas = canvas;
        this.type = type;
        this.chart = null;
    }

    /** @param {Object} data Chart.js `data` config (labels + datasets) */
    render(data, options) {
        const mergedOptions = Object.assign(
            { responsive: true, maintainAspectRatio: false },
            options || {},
        );

        if (this.chart) {
            this.chart.data = data;
            this.chart.options = mergedOptions;
            this.chart.update();

            return;
        }

        // eslint-disable-next-line no-undef -- Chart is a global from the CDN <script> tag (decisions/IDR-012).
        this.chart = new Chart(this.canvas, {
            type: this.type,
            data: data,
            options: mergedOptions,
        });
    }

    destroy() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}
