/**
 * Casana Chart Helpers
 * Wrapper functions for Chart.js with Casana branding
 * Enhanced with gradients, animations, and reference lines
 */

const CasanaCharts = {
    /**
     * Default chart options with Casana styling
     */
    defaultOptions: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 1000,
            easing: 'easeOutQuart',
        },
        plugins: {
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                titleFont: {
                    family: "'Plus Jakarta Sans', sans-serif",
                    size: 13,
                    weight: 600,
                },
                bodyFont: {
                    family: "'Plus Jakarta Sans', sans-serif",
                    size: 12,
                },
                padding: 12,
                cornerRadius: 10,
                borderColor: 'rgba(148, 163, 184, 0.2)',
                borderWidth: 1,
                displayColors: true,
                boxPadding: 4,
                usePointStyle: true,
            },
        },
        interaction: {
            mode: 'index',
            intersect: false,
        },
    },
    
    /**
     * Create a gradient for chart fills
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {string} color - Base color
     * @param {number} alpha - Opacity (0-1)
     * @returns {CanvasGradient}
     */
    createGradient(ctx, color, alpha = 0.3) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, this.hexToRgba(color, alpha));
        gradient.addColorStop(1, this.hexToRgba(color, 0));
        return gradient;
    },
    
    /**
     * Convert hex color to rgba
     * @param {string} hex - Hex color
     * @param {number} alpha - Opacity
     * @returns {string}
     */
    hexToRgba(hex, alpha) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    },
    
    /**
     * Create reference line annotation plugin config
     * @param {number} value - Y-axis value for line
     * @param {string} label - Label text
     * @param {string} color - Line color
     * @returns {object}
     */
    createReferenceLine(value, label, color = '#94a3b8') {
        return {
            type: 'line',
            yMin: value,
            yMax: value,
            borderColor: color,
            borderWidth: 1,
            borderDash: [6, 4],
            label: {
                display: true,
                content: label,
                position: 'end',
                backgroundColor: 'transparent',
                color: color,
                font: {
                    size: 10,
                    family: "'Plus Jakarta Sans', sans-serif",
                },
            },
        };
    },
    
    /**
     * Get colors based on current theme
     * @returns {object} Color palette
     */
    getColors() {
        if (typeof ThemeManager !== 'undefined') {
            return ThemeManager.getChartColors();
        }
        
        // Fallback colors
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        return {
            primary: '#6A6EFF',
            success: isDark ? '#2DD4A7' : '#00594B',
            warning: isDark ? '#FBBF24' : '#F09C4F',
            danger: isDark ? '#F87171' : '#C24D70',
            text: isDark ? '#F1F3F5' : '#212529',
            textSecondary: isDark ? '#ADB5BD' : '#6C757D',
            grid: isDark ? '#2D3142' : '#E9ECEF',
            background: isDark ? '#1E2132' : '#FFFFFF',
        };
    },
    
    /**
     * Create a line chart for health trends
     * @param {HTMLCanvasElement} canvas - Canvas element
     * @param {object} config - Chart configuration
     * @returns {Chart} Chart instance
     */
    createLineChart(canvas, config) {
        const colors = this.getColors();
        
        const defaultConfig = {
            type: 'line',
            options: {
                ...this.defaultOptions,
                scales: {
                    x: {
                        grid: {
                            color: colors.grid,
                            drawBorder: false,
                        },
                        ticks: {
                            color: colors.textSecondary,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                            },
                        },
                    },
                    y: {
                        grid: {
                            color: colors.grid,
                            drawBorder: false,
                        },
                        ticks: {
                            color: colors.textSecondary,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                            },
                        },
                    },
                },
                elements: {
                    line: {
                        tension: 0.3,
                        borderWidth: 2,
                    },
                    point: {
                        radius: 3,
                        hoverRadius: 5,
                    },
                },
            },
        };
        
        const mergedConfig = this.mergeDeep(defaultConfig, config);
        return new Chart(canvas, mergedConfig);
    },
    
    /**
     * Create a sparkline (mini line chart)
     * @param {HTMLCanvasElement} canvas - Canvas element
     * @param {Array} data - Array of values
     * @param {string} color - Line color
     * @returns {Chart} Chart instance
     */
    createSparkline(canvas, data, color = null) {
        const colors = this.getColors();
        const lineColor = color || colors.primary;
        
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.map((_, i) => i),
                datasets: [{
                    data: data,
                    borderColor: lineColor,
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                },
                scales: {
                    x: { display: false },
                    y: { display: false },
                },
            },
        });
    },
    
    /**
     * Create a doughnut chart
     * @param {HTMLCanvasElement} canvas - Canvas element
     * @param {object} config - Chart configuration
     * @returns {Chart} Chart instance
     */
    createDoughnutChart(canvas, config) {
        const colors = this.getColors();
        
        const defaultConfig = {
            type: 'doughnut',
            options: {
                ...this.defaultOptions,
                cutout: '70%',
                plugins: {
                    ...this.defaultOptions.plugins,
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: colors.text,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                            },
                            padding: 16,
                        },
                    },
                },
            },
        };
        
        const mergedConfig = this.mergeDeep(defaultConfig, config);
        return new Chart(canvas, mergedConfig);
    },
    
    /**
     * Create a bar chart
     * @param {HTMLCanvasElement} canvas - Canvas element
     * @param {object} config - Chart configuration
     * @returns {Chart} Chart instance
     */
    createBarChart(canvas, config) {
        const colors = this.getColors();
        
        const defaultConfig = {
            type: 'bar',
            options: {
                ...this.defaultOptions,
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: colors.textSecondary,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                            },
                        },
                    },
                    y: {
                        grid: {
                            color: colors.grid,
                            drawBorder: false,
                        },
                        ticks: {
                            color: colors.textSecondary,
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                            },
                        },
                    },
                },
                borderRadius: 4,
            },
        };
        
        const mergedConfig = this.mergeDeep(defaultConfig, config);
        return new Chart(canvas, mergedConfig);
    },
    
    /**
     * Create an ECG trace chart with zoom capabilities
     * @param {HTMLCanvasElement} canvas - Canvas element
     * @param {Array} ecgData - ECG trace points (1000 values)
     * @returns {Chart} Chart instance
     */
    createECGChart(canvas, ecgData) {
        const colors = this.getColors();
        
        // Generate time labels (10 seconds at 100Hz = 1000 points)
        const labels = ecgData.map((_, i) => (i / 100).toFixed(1));
        
        const chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: ecgData,
                    borderColor: colors.danger,
                    backgroundColor: 'transparent',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { family: "'Plus Jakarta Sans', sans-serif" },
                        bodyFont: { family: "'Plus Jakarta Sans', sans-serif" },
                        callbacks: {
                            title: function(items) {
                                return items[0].label + 's';
                            },
                            label: function(item) {
                                return item.raw.toFixed(3) + ' mV';
                            }
                        }
                    },
                    zoom: {
                        pan: {
                            enabled: true,
                            mode: 'x',
                        },
                        zoom: {
                            wheel: {
                                enabled: true,
                            },
                            pinch: {
                                enabled: true,
                            },
                            drag: {
                                enabled: true,
                                backgroundColor: 'rgba(106, 110, 255, 0.1)',
                                borderColor: 'rgba(106, 110, 255, 0.5)',
                                borderWidth: 1,
                            },
                            mode: 'x',
                        },
                        limits: {
                            x: { min: 'original', max: 'original' },
                        },
                    },
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Time (seconds)',
                            color: colors.textSecondary,
                        },
                        grid: {
                            color: colors.grid,
                        },
                        ticks: {
                            color: colors.textSecondary,
                            maxTicksLimit: 11,
                        },
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'mV',
                            color: colors.textSecondary,
                        },
                        grid: {
                            color: colors.grid,
                        },
                        ticks: {
                            color: colors.textSecondary,
                        },
                    },
                },
            },
        });
        
        return chart;
    },
    
    /**
     * Create a blood pressure range chart
     * @param {HTMLCanvasElement} canvas - Canvas element
     * @param {Array} data - Array of { date, systolic, diastolic }
     * @returns {Chart} Chart instance
     */
    createBPChart(canvas, data) {
        const colors = this.getColors();
        
        const labels = data.map(d => d.date);
        const systolicData = data.map(d => d.systolic);
        const diastolicData = data.map(d => d.diastolic);
        
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Systolic',
                        data: systolicData,
                        borderColor: colors.danger,
                        backgroundColor: `${colors.danger}20`,
                        fill: false,
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3,
                    },
                    {
                        label: 'Diastolic',
                        data: diastolicData,
                        borderColor: colors.primary,
                        backgroundColor: `${colors.primary}20`,
                        fill: false,
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.3,
                    },
                ],
            },
            options: {
                ...this.defaultOptions,
                plugins: {
                    ...this.defaultOptions.plugins,
                    legend: {
                        display: false, // Disable built-in legend - use HTML legend instead for consistency
                    },
                },
                scales: {
                    x: {
                        grid: {
                            color: colors.grid,
                        },
                        ticks: {
                            color: colors.textSecondary,
                        },
                    },
                    y: {
                        grid: {
                            color: colors.grid,
                        },
                        ticks: {
                            color: colors.textSecondary,
                        },
                        suggestedMin: 60,
                        suggestedMax: 180,
                    },
                },
            },
        });
    },
    
    /**
     * Create a gauge-style chart for single values
     * @param {HTMLCanvasElement} canvas - Canvas element
     * @param {number} value - Current value
     * @param {number} min - Minimum value
     * @param {number} max - Maximum value
     * @param {string} label - Value label
     * @returns {Chart} Chart instance
     */
    createGaugeChart(canvas, value, min, max, label) {
        const colors = this.getColors();
        const percentage = ((value - min) / (max - min)) * 100;
        
        // Determine color based on value
        let valueColor = colors.success;
        if (percentage > 75 || percentage < 25) {
            valueColor = colors.danger;
        } else if (percentage > 60 || percentage < 40) {
            valueColor = colors.warning;
        }
        
        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [percentage, 100 - percentage],
                    backgroundColor: [valueColor, colors.grid],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                rotation: -90,
                circumference: 180,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false },
                },
            },
            plugins: [{
                id: 'gaugeText',
                afterDraw: (chart) => {
                    const { ctx, chartArea } = chart;
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = chartArea.bottom - 10;
                    
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.fillStyle = colors.text;
                    ctx.font = "bold 24px 'Plus Jakarta Sans', sans-serif";
                    ctx.fillText(value, centerX, centerY - 10);
                    ctx.font = "12px 'Plus Jakarta Sans', sans-serif";
                    ctx.fillStyle = colors.textSecondary;
                    ctx.fillText(label, centerX, centerY + 10);
                    ctx.restore();
                },
            }],
        });
    },
    
    /**
     * Update chart colors on theme change
     * @param {Chart} chart - Chart instance
     */
    updateChartTheme(chart) {
        const colors = this.getColors();
        
        // Update scales
        if (chart.options.scales) {
            Object.values(chart.options.scales).forEach(scale => {
                if (scale.grid) {
                    scale.grid.color = colors.grid;
                }
                if (scale.ticks) {
                    scale.ticks.color = colors.textSecondary;
                }
            });
        }
        
        chart.update();
    },
    
    /**
     * Deep merge utility
     * @param {object} target - Target object
     * @param {object} source - Source object
     * @returns {object} Merged object
     */
    mergeDeep(target, source) {
        const output = Object.assign({}, target);
        
        if (this.isObject(target) && this.isObject(source)) {
            Object.keys(source).forEach(key => {
                if (this.isObject(source[key])) {
                    if (!(key in target)) {
                        Object.assign(output, { [key]: source[key] });
                    } else {
                        output[key] = this.mergeDeep(target[key], source[key]);
                    }
                } else {
                    Object.assign(output, { [key]: source[key] });
                }
            });
        }
        
        return output;
    },
    
    isObject(item) {
        return (item && typeof item === 'object' && !Array.isArray(item));
    },
};

// Chart registry for theme updates
CasanaCharts.registry = [];

/**
 * Register a chart for automatic theme updates
 * @param {Chart} chart - Chart instance
 */
CasanaCharts.register = function(chart) {
    this.registry.push(chart);
};

/**
 * Unregister a chart (call before destroying)
 * @param {Chart} chart - Chart instance
 */
CasanaCharts.unregister = function(chart) {
    const index = this.registry.indexOf(chart);
    if (index > -1) {
        this.registry.splice(index, 1);
    }
};

/**
 * Update all registered charts for new theme
 */
CasanaCharts.updateAllThemes = function() {
    const colors = this.getColors();
    
    this.registry.forEach(chart => {
        if (chart && chart.options) {
            // Update scales
            if (chart.options.scales) {
                Object.values(chart.options.scales).forEach(scale => {
                    if (scale.grid) {
                        scale.grid.color = colors.grid;
                    }
                    if (scale.ticks) {
                        scale.ticks.color = colors.textSecondary;
                    }
                    if (scale.title) {
                        scale.title.color = colors.textSecondary;
                    }
                });
            }
            
            // Update legend
            if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                chart.options.plugins.legend.labels.color = colors.text;
            }
            
            try {
                chart.update('none'); // 'none' prevents animation during update
            } catch (e) {
                console.warn('Failed to update chart theme:', e);
            }
        }
    });
};

// Listen for theme changes and update all registered charts
window.addEventListener('themechange', () => {
    // Small delay to let CSS variables update
    setTimeout(() => {
        CasanaCharts.updateAllThemes();
    }, 50);
});

// Also listen for the custom event from theme.js
document.addEventListener('DOMContentLoaded', () => {
    // Re-check theme periodically in case of system theme changes
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', () => {
        setTimeout(() => {
            CasanaCharts.updateAllThemes();
        }, 100);
    });
});
