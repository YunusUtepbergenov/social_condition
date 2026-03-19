// Chart.js Global Theme
// Loaded after chart.js CDN, before any chart initialization

var chartColors = {
    primary: 'rgb(68, 119, 170)',
    success: 'rgb(4, 157, 60)',
    danger: 'rgb(220, 53, 69)',
    warning: 'rgb(250, 167, 63)',
    neutral: 'rgb(115, 115, 115)',
    purple: 'rgb(201, 99, 207)',
    gray: 'rgb(160, 160, 160)',
};

// Global defaults
Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';
Chart.defaults.elements.line.tension = 0.3;
Chart.defaults.elements.line.borderWidth = 2;
Chart.defaults.elements.point.radius = 0;
Chart.defaults.elements.point.hoverRadius = 5;
Chart.defaults.elements.point.hoverBorderWidth = 2;
Chart.defaults.elements.point.backgroundColor = '#fff';
Chart.defaults.elements.point.hoverBackgroundColor = '#fff';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.padding = 16;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
Chart.defaults.plugins.tooltip.bodySpacing = 6;
Chart.defaults.plugins.tooltip.usePointStyle = true;
Chart.defaults.scale.grid.color = 'rgba(0, 0, 0, 0.06)';
Chart.defaults.scale.grid.drawBorder = false;
Chart.defaults.scale.ticks.padding = 8;

// Convert any CSS color to rgba string
function colorToRgba(color, alpha) {
    // Handle rgb() format
    if (color.indexOf('rgb(') === 0) {
        return color.replace('rgb(', 'rgba(').replace(')', ', ' + alpha + ')');
    }
    // Handle hex format
    if (color.indexOf('#') === 0) {
        var hex = color.slice(1);
        if (hex.length === 3) {
            hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        }
        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
    }
    return color;
}

// Helper: create vertical gradient fill for line charts
function createGradient(ctx, color, height) {
    var gradient = ctx.createLinearGradient(0, 0, 0, height || 300);
    gradient.addColorStop(0, colorToRgba(color, 0.25));
    gradient.addColorStop(1, colorToRgba(color, 0.02));
    return gradient;
}

// Plugin: auto-apply gradient backgrounds for line datasets with fill: true
Chart.register({
    id: 'autoGradient',
    beforeUpdate: function(chart) {
        chart.data.datasets.forEach(function(dataset) {
            if (dataset.fill && dataset.type !== 'bar' && dataset.borderColor && chart.ctx) {
                var bg = dataset.backgroundColor;
                // Only apply gradient if backgroundColor is not already set or is a simple fill
                if (!bg || typeof bg === 'string') {
                    var color = dataset.borderColor;
                    if (typeof color === 'string' && (color.indexOf('rgb') === 0 || color.indexOf('#') === 0)) {
                        dataset.backgroundColor = createGradient(chart.ctx, color, chart.chartArea ? chart.chartArea.bottom : chart.height || 300);
                    }
                }
            }
        });
    }
});

// Format numbers with space separators
function formatChartNumber(value) {
    if (value === null || value === undefined) {
        return '';
    }
    var parts = String(value).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    return parts.join(',');
}
