'use strict';

/** Renders the Dashboard Data API's own `warnings` array (DashboardAlert DTOs
 *  — level/identifier/message, see DashboardResponseSerializer::alert()) as
 *  Bootstrap alerts. Never invents a warning; renders exactly what the API
 *  already decided to report. */

const LEVEL_TO_BOOTSTRAP_VARIANT = {
    info: 'info',
    warning: 'warning',
    critical: 'danger',
};

export class DashboardAlert {
    constructor(container) {
        this.container = container;
    }

    render(alerts) {
        this.container.textContent = '';

        if (!alerts || alerts.length === 0) {
            this.container.classList.add('d-none');
            return;
        }

        this.container.classList.remove('d-none');

        alerts.forEach((alert) => {
            const variant = LEVEL_TO_BOOTSTRAP_VARIANT[alert.level] || 'secondary';

            const div = document.createElement('div');
            div.className = 'alert alert-' + variant + ' py-2 mb-2';
            div.setAttribute('role', 'alert');
            div.textContent = alert.message; // textContent only — never innerHTML with a raw value.

            this.container.appendChild(div);
        });
    }
}
