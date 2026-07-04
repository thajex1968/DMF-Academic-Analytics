'use strict';

/**
 * Dashboard Presentation Layer entry point (Sprint 5). Orchestrates
 * fetch → render only — every number shown comes directly from the
 * Dashboard Data API (Sprint 4 Phase 3, unmodified); no calculation happens
 * here (Architecture Rules).
 */

import { apiRequest, getToken } from './api.js';
import { DashboardLoader } from './components/DashboardLoader.js';
import { DashboardEmptyState } from './components/DashboardEmptyState.js';
import { DashboardErrorState } from './components/DashboardErrorState.js';
import { DashboardAlert } from './components/DashboardAlert.js';
import { DashboardCard } from './components/DashboardCard.js';
import { DashboardChart } from './components/DashboardChart.js';
import { DashboardTable } from './components/DashboardTable.js';

const THEME_KEY = 'dlap_dashboard_theme';

const loader = new DashboardLoader(document.getElementById('dashboard-loader'));
const emptyState = new DashboardEmptyState(document.getElementById('dashboard-empty'));
const errorState = new DashboardErrorState(document.getElementById('dashboard-error'), () => loadDashboard());
const alertList = new DashboardAlert(document.getElementById('dashboard-alerts'));
const cards = new DashboardCard(document.getElementById('dashboard-cards'));

const charts = {
    subjectComparison: new DashboardChart(document.getElementById('chart-subject-comparison'), 'bar'),
    standardPerformance: new DashboardChart(document.getElementById('chart-standard-performance'), 'line'),
    strandPerformance: new DashboardChart(document.getElementById('chart-strand-performance'), 'radar'),
    difficultyDistribution: new DashboardChart(document.getElementById('chart-difficulty-distribution'), 'pie'),
    benchmarkComparison: new DashboardChart(document.getElementById('chart-benchmark-comparison'), 'bar'),
};

const tables = {
    subjects: new DashboardTable(document.getElementById('table-subjects')),
    standards: new DashboardTable(document.getElementById('table-standards')),
};

const contentContainer = document.getElementById('dashboard-content');

let isLoading = false;

function showContent() {
    contentContainer.classList.remove('d-none');
    emptyState.hide();
    errorState.hide();
}

function hideContent() {
    contentContainer.classList.add('d-none');
}

/** @param {string} chartEmptyElementId @param {boolean} isEmpty */
function toggleChartEmptyState(canvas, emptyContainerId, isEmpty, message) {
    const emptyContainer = document.getElementById(emptyContainerId);

    if (isEmpty) {
        canvas.classList.add('d-none');
        emptyContainer.classList.remove('d-none');
        emptyContainer.textContent = message;
    } else {
        canvas.classList.remove('d-none');
        emptyContainer.classList.add('d-none');
        emptyContainer.textContent = '';
    }
}

function renderCards(overview, health) {
    const assessment = overview.assessments[0] || null;
    const subject = overview.subjects[0] || null;
    const benchmarkAverage = averageBenchmarkDifference(overview.benchmarks);

    const analyticsOk = health.analytics_status === 'ok';
    const importOk = health.import_status === 'ok';

    cards.render([
        {
            label: 'การประเมิน',
            value: overview.metadata.subject_code + ' ' + overview.metadata.academic_year,
            unit: null,
        },
        {
            label: 'จำนวนนักเรียน',
            value: assessment ? assessment.student_count : null,
            unit: null,
        },
        {
            label: 'คะแนนเฉลี่ย (ร้อยละถูกต้อง)',
            value: assessment ? assessment.percent_correct : null,
            unit: '%',
        },
        {
            label: 'คะแนนสูงสุด',
            value: subject ? subject.highest : null,
            unit: null,
        },
        {
            label: 'คะแนนต่ำสุด',
            value: subject ? subject.lowest : null,
            unit: null,
        },
        {
            label: 'ความยากเฉลี่ย (Difficulty)',
            value: null, // Not exposed by the Dashboard Data API — decisions/IDR-012.
            unit: null,
        },
        {
            label: 'สถานะระบบ',
            value: analyticsOk && importOk ? 'ปกติ' : 'ควรตรวจสอบ',
            unit: null,
        },
        {
            label: 'เทียบเคียง (Benchmark) เฉลี่ย',
            value: benchmarkAverage,
            unit: '%',
        },
    ]);
}

function averageBenchmarkDifference(benchmarks) {
    if (!benchmarks || benchmarks.length === 0) {
        return null;
    }

    const sum = benchmarks.reduce((total, benchmark) => total + benchmark.difference, 0);

    return sum / benchmarks.length;
}

function renderCharts(overview) {
    renderSubjectComparison(overview.subjects);
    renderStandardPerformance(overview.standards);
    renderStrandPerformance(overview.strands);
    renderDifficultyDistribution();
    renderBenchmarkComparison(overview.benchmarks);
}

function renderSubjectComparison(subjects) {
    const isEmpty = subjects.length === 0;
    toggleChartEmptyState(
        charts.subjectComparison.canvas,
        'empty-subject-comparison',
        isEmpty,
        'ไม่มีข้อมูลรายวิชาในการประเมินนี้',
    );

    if (isEmpty) {
        charts.subjectComparison.destroy();
        return;
    }

    charts.subjectComparison.render({
        labels: subjects.map((subject) => subject.subject_code),
        datasets: [
            {
                label: 'ร้อยละถูกต้อง',
                data: subjects.map((subject) => toPercent(subject.percent_correct)),
            },
        ],
    });
}

function renderStandardPerformance(standards) {
    const isEmpty = standards.length === 0;
    toggleChartEmptyState(
        charts.standardPerformance.canvas,
        'empty-standard-performance',
        isEmpty,
        'ไม่มีข้อมูลมาตรฐานการเรียนรู้ในการประเมินนี้',
    );

    if (isEmpty) {
        charts.standardPerformance.destroy();
        return;
    }

    charts.standardPerformance.render({
        labels: standards.map((standard) => standard.standard_code),
        datasets: [
            {
                label: 'ร้อยละถูกต้อง',
                data: standards.map((standard) => toPercent(standard.percent_correct)),
            },
        ],
    });
}

function renderStrandPerformance(strands) {
    const isEmpty = strands.length === 0;
    toggleChartEmptyState(
        charts.strandPerformance.canvas,
        'empty-strand-performance',
        isEmpty,
        'ไม่มีข้อมูลสาระการเรียนรู้ในการประเมินนี้',
    );

    if (isEmpty) {
        charts.strandPerformance.destroy();
        return;
    }

    charts.strandPerformance.render({
        labels: strands.map((strand) => strand.strand_code),
        datasets: [
            {
                label: 'ร้อยละถูกต้อง',
                data: strands.map((strand) => toPercent(strand.percent_correct)),
            },
        ],
    });
}

function renderDifficultyDistribution() {
    // The Dashboard Data API does not expose per-question difficulty at all
    // (decisions/IDR-012) — this chart is always an honest empty state today.
    toggleChartEmptyState(
        charts.difficultyDistribution.canvas,
        'empty-difficulty-distribution',
        true,
        'ยังไม่มีข้อมูลค่าความยากในระบบ Dashboard Data API ปัจจุบัน',
    );
    charts.difficultyDistribution.destroy();
}

function renderBenchmarkComparison(benchmarks) {
    const isEmpty = benchmarks.length === 0;
    toggleChartEmptyState(
        charts.benchmarkComparison.canvas,
        'empty-benchmark-comparison',
        isEmpty,
        'ยังไม่มีข้อมูลค่าเทียบเคียง (Benchmark) สำหรับการประเมินนี้',
    );

    if (isEmpty) {
        charts.benchmarkComparison.destroy();
        return;
    }

    charts.benchmarkComparison.render({
        labels: benchmarks.map((benchmark) => benchmark.scope),
        datasets: [
            { label: 'โรงเรียน', data: benchmarks.map((b) => toPercent(b.school_percent_correct)) },
            { label: 'ค่าเทียบเคียง', data: benchmarks.map((b) => toPercent(b.benchmark_percent_correct)) },
        ],
    });
}

function toPercent(ratio) {
    return ratio === null || ratio === undefined ? null : Number((ratio * 100).toFixed(1));
}

function renderTables(overview) {
    tables.subjects.render(
        [
            { key: 'subject_code', label: 'วิชา' },
            { key: 'percent_correct', label: 'ร้อยละถูกต้อง', format: 'percent' },
            { key: 'average', label: 'คะแนนเฉลี่ย' },
            { key: 'highest', label: 'สูงสุด' },
            { key: 'lowest', label: 'ต่ำสุด' },
        ],
        overview.subjects,
        'ไม่มีข้อมูลรายวิชา',
    );

    tables.standards.render(
        [
            { key: 'standard_code', label: 'มาตรฐาน' },
            { key: 'percent_correct', label: 'ร้อยละถูกต้อง', format: 'percent' },
            { key: 'mean', label: 'ค่าเฉลี่ย' },
            { key: 'median', label: 'มัธยฐาน' },
            { key: 'min', label: 'ต่ำสุด' },
            { key: 'max', label: 'สูงสุด' },
            { key: 'standard_deviation', label: 'ส่วนเบี่ยงเบนมาตรฐาน' },
        ],
        overview.standards,
        'ไม่มีข้อมูลมาตรฐานการเรียนรู้',
    );
}

function renderAssessmentSelector(overview) {
    const select = document.getElementById('assessment-select');
    select.textContent = '';

    const option = document.createElement('option');
    option.value = String(overview.metadata.assessment_id);
    option.textContent = overview.metadata.subject_code + ' · ' + overview.metadata.academic_year;
    select.appendChild(option);
}

async function loadDashboard() {
    if (isLoading) {
        return;
    }

    isLoading = true;
    document.getElementById('refresh-button').disabled = true;
    loader.show();
    hideContent();
    errorState.hide();
    emptyState.hide();

    try {
        const [overview, health] = await Promise.all([
            apiRequest('dashboard_overview'),
            apiRequest('dashboard_health'),
        ]);

        if (overview.message) {
            emptyState.show(overview.message);
            return;
        }

        renderAssessmentSelector(overview);
        alertList.render(overview.warnings);
        renderCards(overview, health);
        renderCharts(overview);
        renderTables(overview);
        showContent();
    } catch (error) {
        if (error.status === 401) {
            window.location.href = '../index.html';
            return;
        }

        errorState.show(error.message);
    } finally {
        loader.hide();
        isLoading = false;
        document.getElementById('refresh-button').disabled = false;
    }
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.getElementById('theme-toggle-label').textContent = theme === 'dark' ? 'โหมดสว่าง' : 'โหมดมืด';
    document.getElementById('theme-toggle').setAttribute('aria-pressed', String(theme === 'dark'));
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
}

document.addEventListener('DOMContentLoaded', function () {
    if (!getToken()) {
        window.location.href = '../index.html';
        return;
    }

    applyTheme(localStorage.getItem(THEME_KEY) || 'light');

    document.getElementById('theme-toggle').addEventListener('click', toggleTheme);
    document.getElementById('refresh-button').addEventListener('click', () => loadDashboard());

    loadDashboard();
});
