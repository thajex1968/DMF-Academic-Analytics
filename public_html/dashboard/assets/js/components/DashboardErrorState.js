'use strict';

/** Renders an error message with a retry button. The retry callback is
 *  supplied by the caller (dashboard.js) — this component has no fetch logic
 *  of its own. */
export class DashboardErrorState {
    constructor(container, onRetry) {
        this.container = container;
        this.onRetry = onRetry;
    }

    show(message) {
        this.container.textContent = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'text-center py-4';

        const paragraph = document.createElement('p');
        paragraph.className = 'text-danger mb-3';
        paragraph.setAttribute('role', 'alert');
        paragraph.textContent = message;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-danger btn-sm';
        button.textContent = 'ลองใหม่';
        button.addEventListener('click', () => this.onRetry());

        wrapper.appendChild(paragraph);
        wrapper.appendChild(button);
        this.container.appendChild(wrapper);
        this.container.classList.remove('d-none');
    }

    hide() {
        this.container.classList.add('d-none');
        this.container.textContent = '';
    }
}
