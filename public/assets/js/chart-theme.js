// Chart.js Global Theme — Modern & Eye-catching
// Loaded after chart.js CDN, before any chart initialization

var chartColors = {
    primary: 'rgb(59, 130, 246)',    // vivid blue
    secondary: 'rgb(16, 185, 129)',  // emerald green
    danger: 'rgb(239, 68, 68)',      // bright red
    warning: 'rgb(245, 158, 11)',    // amber
    neutral: 'rgb(107, 114, 128)',   // cool gray
    purple: 'rgb(139, 92, 246)',     // violet
    gray: 'rgb(156, 163, 175)',      // light gray
};

// Global defaults
Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#4b5563';
Chart.defaults.elements.line.tension = 0.35;
Chart.defaults.elements.line.borderWidth = 2.5;
Chart.defaults.elements.line.borderCapStyle = 'round';
Chart.defaults.elements.line.borderJoinStyle = 'round';
Chart.defaults.elements.point.radius = 0;
Chart.defaults.elements.point.hoverRadius = 6;
Chart.defaults.elements.point.hoverBorderWidth = 3;
Chart.defaults.elements.point.hoverBorderColor = '#fff';
Chart.defaults.elements.point.hitRadius = 20;
Chart.defaults.elements.bar.borderWidth = 0;
Chart.defaults.elements.bar.borderRadius = 6;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyleWidth = 10;
Chart.defaults.plugins.legend.labels.padding = 20;
Chart.defaults.plugins.legend.labels.font = { size: 12, weight: '600' };
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
Chart.defaults.plugins.tooltip.titleColor = '#f1f5f9';
Chart.defaults.plugins.tooltip.bodyColor = '#cbd5e1';
Chart.defaults.plugins.tooltip.borderColor = 'rgba(148, 163, 184, 0.2)';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 10;
Chart.defaults.plugins.tooltip.padding = { top: 10, bottom: 10, left: 14, right: 14 };
Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: '600' };
Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
Chart.defaults.plugins.tooltip.bodySpacing = 6;
Chart.defaults.plugins.tooltip.usePointStyle = true;
Chart.defaults.plugins.tooltip.boxPadding = 6;
Chart.defaults.plugins.tooltip.displayColors = true;
Chart.defaults.scale.grid.color = 'rgba(148, 163, 184, 0.08)';
Chart.defaults.scale.grid.drawBorder = false;
Chart.defaults.scale.ticks.padding = 8;
Chart.defaults.scale.ticks.font = { size: 11, weight: '500' };
Chart.defaults.scale.ticks.color = '#374151';

// Plugin: trim day from date labels (2025-06-01 → 2025-06)
Chart.register({
    id: 'trimDateLabels',
    beforeUpdate: function(chart) {
        var labels = chart.data.labels;
        if (!labels || !labels.length) return;
        for (var i = 0; i < labels.length; i++) {
            if (typeof labels[i] === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(labels[i])) {
                labels[i] = labels[i].substring(0, 7);
            }
        }
    }
});

// Convert any CSS color to rgba string
function colorToRgba(color, alpha) {
    if (color.indexOf('rgb(') === 0) {
        return color.replace('rgb(', 'rgba(').replace(')', ', ' + alpha + ')');
    }
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
    var h = height || 300;
    var gradient = ctx.createLinearGradient(0, 0, 0, h);
    gradient.addColorStop(0, colorToRgba(color, 0.20));
    gradient.addColorStop(0.6, colorToRgba(color, 0.05));
    gradient.addColorStop(1, colorToRgba(color, 0));
    return gradient;
}

// Plugin: auto-apply gradient backgrounds for line datasets with fill: true
Chart.register({
    id: 'autoGradient',
    beforeUpdate: function(chart) {
        chart.data.datasets.forEach(function(dataset) {
            if (dataset.fill && dataset.type !== 'bar' && dataset.borderColor && chart.ctx) {
                var bg = dataset.backgroundColor;
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

// Plugin: vertical hover line (crosshair)
Chart.register({
    id: 'hoverLine',
    afterDraw: function(chart) {
        if (chart.tooltip && chart.tooltip._active && chart.tooltip._active.length) {
            var x = chart.tooltip._active[0].element.x;
            var yAxis = chart.scales.y;
            var ctx = chart.ctx;
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(x, yAxis.top);
            ctx.lineTo(x, yAxis.bottom);
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'rgba(148, 163, 184, 0.3)';
            ctx.setLineDash([4, 4]);
            ctx.stroke();
            ctx.restore();
        }
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
