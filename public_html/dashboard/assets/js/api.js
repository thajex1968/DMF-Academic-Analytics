'use strict';

/**
 * Thin fetch wrapper for the Dashboard Data API (Sprint 4 Phase 3, unchanged
 * this Sprint). Self-contained rather than importing public_html/assets/js/app.js:
 * app.js's API_URL is a same-directory relative path ('api/index.php'), which
 * resolves incorrectly from this page's own directory (public_html/dashboard/);
 * app.js is Sprint 3's frozen Web Foundation and is not modified to fix that
 * for this Sprint's sake (decisions/IDR-012).
 *
 * Auth token is read from the same sessionStorage key app.js already writes —
 * logging in via the existing SPA shell (public_html/index.html) is what
 * populates it; this page never issues its own token.
 */

const TOKEN_KEY = 'dlap_token';
const API_URL = '../api/index.php';

export function getToken() {
    return sessionStorage.getItem(TOKEN_KEY);
}

/** Every server-provided string rendered by this page goes through textContent
 *  or this helper — never raw innerHTML interpolation (Phase 8, Security). */
export function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

/**
 * GET-only — every Dashboard Data API route is a read (Architecture Rules:
 * Dashboard Actions never calculate, this page never writes).
 */
export async function apiRequest(action) {
    const headers = {};
    const token = getToken();

    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }

    const response = await fetch(API_URL + '?action=' + encodeURIComponent(action), {
        method: 'GET',
        headers: headers,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.error || 'ไม่สามารถโหลดข้อมูลแดชบอร์ดได้');
        error.status = response.status;
        throw error;
    }

    return data;
}
