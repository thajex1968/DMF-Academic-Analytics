'use strict';

/**
 * DMF Learning Analytics Platform — SPA shell client (T1.6/T1.7,
 * decisions/IDR-010). Vanilla JavaScript, no framework, no build step —
 * matching CLAUDE.md's documented frontend stack and grade.dmf.ac.th's
 * no-build-step precedent.
 *
 * Auth state lives in sessionStorage only (cleared when the tab closes) —
 * never a cookie, so there is no ambient browser-attached credential and
 * therefore nothing for CSRF to forge (decisions/IDR-010 §1).
 */

const TOKEN_KEY = 'dlap_token';

const API_URL = 'api/index.php';

/** Every server-provided string is inserted via textContent, never innerHTML,
 *  so no escaping helper is needed for text — but building HTML fragments (the
 *  recent-import-jobs table) still requires manual escaping of any embedded
 *  server value.
 */
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function getToken() {
    return sessionStorage.getItem(TOKEN_KEY);
}

function setToken(token) {
    sessionStorage.setItem(TOKEN_KEY, token);
}

function clearToken() {
    sessionStorage.removeItem(TOKEN_KEY);
}

async function apiRequest(action, method, body) {
    const headers = { 'Content-Type': 'application/json' };
    const token = getToken();

    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }

    const response = await fetch(API_URL + '?action=' + encodeURIComponent(action), {
        method: method,
        headers: headers,
        body: body ? JSON.stringify(body) : undefined,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.error || 'เกิดข้อผิดพลาด');
        error.status = response.status;
        throw error;
    }

    return data;
}

function showFlash(message, variant) {
    variant = variant || 'danger';

    const container = document.getElementById('flash-container');
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + variant + ' shadow-sm';
    toast.setAttribute('role', 'alert');
    toast.textContent = message; // textContent — never innerHTML with a raw value.

    container.appendChild(toast);

    setTimeout(function () {
        toast.remove();
    }, 5000);
}

function showLoginView() {
    document.getElementById('login-view').classList.remove('hidden');
    document.getElementById('app-shell').classList.add('hidden');
}

function showAppShell() {
    document.getElementById('login-view').classList.add('hidden');
    document.getElementById('app-shell').classList.remove('hidden');
}

const STATUS_LABELS_TH = {
    queued: 'รอดำเนินการ',
    processing: 'กำลังดำเนินการ',
    committed: 'สำเร็จ',
    failed: 'ล้มเหลว',
};

function renderDashboard(data) {
    document.getElementById('stat-app-version').textContent =
        data.app.name + ' v' + data.app.version;
    document.getElementById('footer-version').textContent =
        data.app.name + ' v' + data.app.version + ' (' + data.app.env + ')';

    document.getElementById('stat-user').textContent =
        data.user.display_name || data.user.username;
    document.getElementById('nav-user-display').textContent =
        (data.user.display_name || data.user.username) + ' (' + data.user.role + ')';

    document.getElementById('stat-school').textContent = data.school.name || '-';

    document.getElementById('stat-system-status').textContent =
        data.system_status.database === 'ok' ? 'ปกติ' : 'มีปัญหา';

    document.getElementById('import-total').textContent = data.import_statistics.total;
    document.getElementById('import-queued').textContent = data.import_statistics.queued;
    document.getElementById('import-processing').textContent = data.import_statistics.processing;
    document.getElementById('import-committed').textContent = data.import_statistics.committed;
    document.getElementById('import-failed').textContent = data.import_statistics.failed;

    const tbody = document.getElementById('recent-import-jobs');

    if (data.recent_import_jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center py-3">ไม่มีข้อมูล</td></tr>';
        return;
    }

    tbody.innerHTML = data.recent_import_jobs
        .map(function (job) {
            const statusLabel = STATUS_LABELS_TH[job.status] || job.status;

            return (
                '<tr>' +
                '<td>#' + escapeHtml(job.id) + '</td>' +
                '<td>' + escapeHtml(statusLabel) + '</td>' +
                '<td>' + escapeHtml(job.file_type) + '</td>' +
                '<td>' + escapeHtml(job.created_at) + '</td>' +
                '</tr>'
            );
        })
        .join('');
}

async function loadDashboard() {
    try {
        const data = await apiRequest('dashboard_summary', 'GET');
        showAppShell();
        renderDashboard(data);
    } catch (error) {
        if (error.status === 401) {
            clearToken();
            showLoginView();
            showFlash('เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่', 'warning');
            return;
        }

        showFlash(error.message, 'danger');
    }
}

function handleLoginSubmit(event) {
    event.preventDefault();

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorBox = document.getElementById('login-error');
    const submitButton = document.getElementById('login-submit');

    errorBox.classList.add('d-none');
    submitButton.disabled = true;

    apiRequest('login_staff', 'POST', { username: username, password: password })
        .then(function (data) {
            setToken(data.token);
            document.getElementById('password').value = ''; // never linger in the DOM/form state.
            return loadDashboard();
        })
        .catch(function (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('d-none');
        })
        .finally(function () {
            submitButton.disabled = false;
        });
}

function handleLogout() {
    apiRequest('logout_staff', 'POST')
        .catch(function () {
            // Best-effort server round-trip (see LogoutStaffAction's docblock) — the
            // client-side token removal below is what actually ends the session.
        })
        .finally(function () {
            clearToken();
            showLoginView();
            document.getElementById('login-form').reset();
        });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('login-form').addEventListener('submit', handleLoginSubmit);
    document.getElementById('logout-button').addEventListener('click', handleLogout);

    if (getToken()) {
        loadDashboard();
    } else {
        showLoginView();
    }
});
