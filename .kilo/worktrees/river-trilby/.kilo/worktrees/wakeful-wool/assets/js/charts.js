/* ============================================================
   charts.js - Chart.js helpers & theme-aware default colors
   ============================================================ */

// Detect current theme
function isDarkMode() {
    return document.documentElement.getAttribute('data-theme') === 'dark';
}

// Chart.js global font color based on theme
function chartTextColor() {
    return isDarkMode() ? '#ffffff' : '#000000';
}
function chartGridColor() {
    return isDarkMode() ? '#2c3849' : '#e3e8f0';
}

// Brand palette
const CHART_COLORS = ['#e63946', '#2a9d8f', '#f4a261', '#457b9d', '#8e5bbf', '#e76f51', '#f4a261', '#2a9d8f'];

// Default chart options (theme-aware)
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: chartTextColor(), usePointStyle: true, padding: 16 } },
        tooltip: {
            backgroundColor: isDarkMode() ? '#1a2231' : '#ffffff',
            titleColor: isDarkMode() ? '#ffffff' : '#000000',
            bodyColor: isDarkMode() ? '#ffffff' : '#000000',
            borderColor: isDarkMode() ? '#2c3849' : '#e3e8f0',
            borderWidth: 1,
            padding: 12,
            boxPadding: 6
        }
    },
    scales: {
        x: { grid: { color: chartGridColor() }, ticks: { color: chartTextColor() } },
        y: { grid: { color: chartGridColor() }, ticks: { color: chartTextColor() } }
    }
};

// Create a line chart
function createLineChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            ...JSON.parse(JSON.stringify(chartDefaults)),
            plugins: { ...chartDefaults.plugins },
            scales: JSON.parse(JSON.stringify(chartDefaults.scales))
        }
    });
}

// Create a bar chart
function createBarChart(canvasId, labels, datasets) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            ...JSON.parse(JSON.stringify(chartDefaults)),
            plugins: { ...chartDefaults.plugins },
            scales: JSON.parse(JSON.stringify(chartDefaults.scales))
        }
    });
}

// Create a doughnut chart
function createDoughnutChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: CHART_COLORS,
                borderWidth: 2,
                borderColor: isDarkMode() ? '#1a2231' : '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: { position: 'bottom', labels: { color: chartTextColor(), usePointStyle: true, padding: 12 } },
                tooltip: chartDefaults.plugins.tooltip
            }
        }
    });
}
