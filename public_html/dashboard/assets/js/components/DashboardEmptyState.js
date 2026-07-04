'use strict';

/** Renders an honest "nothing to show" message — used both for "no
 *  assessment yet" (Dashboard Data API's real response) and for any Dashboard
 *  section the frozen API does not currently expose data for
 *  (decisions/IDR-012). Never fabricates a placeholder value. */
export class DashboardEmptyState {
    constructor(container) {
        this.container = container;
    }

    show(message) {
        this.container.textContent = '';

        const paragraph = document.createElement('p');
        paragraph.className = 'text-muted text-center py-4 mb-0';
        paragraph.textContent = message;

        this.container.appendChild(paragraph);
        this.container.classList.remove('d-none');
    }

    hide() {
        this.container.classList.add('d-none');
        this.container.textContent = '';
    }
}
