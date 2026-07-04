'use strict';

/** Shows/hides a loading indicator inside a given container. Pure DOM state
 *  toggling — no business logic (Architecture: JavaScript is fetch/render/
 *  filter/interaction only). */
export class DashboardLoader {
    constructor(container) {
        this.container = container;
    }

    show() {
        this.container.classList.remove('d-none');
    }

    hide() {
        this.container.classList.add('d-none');
    }
}
