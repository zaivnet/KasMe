import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

const isDark = () => document.documentElement.classList.contains('dark');

Chart.defaults.color = isDark() ? '#94a3b8' : '#64748b';
Chart.defaults.borderColor = isDark() ? 'rgba(71, 85, 105, .35)' : 'rgba(203, 213, 225, .65)';
Chart.defaults.font.family = "'Aptos', 'Segoe UI Variable', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.plugins.legend.labels.boxHeight = 8;
Chart.defaults.plugins.legend.labels.padding = 18;
Chart.defaults.plugins.tooltip.backgroundColor = isDark() ? 'rgba(15, 23, 42, .96)' : 'rgba(255, 255, 255, .98)';
Chart.defaults.plugins.tooltip.titleColor = isDark() ? '#f8fafc' : '#0f172a';
Chart.defaults.plugins.tooltip.bodyColor = isDark() ? '#cbd5e1' : '#475569';
Chart.defaults.plugins.tooltip.borderColor = isDark() ? '#334155' : '#e2e8f0';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 12;
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.datasets.bar.borderRadius = 6;
Chart.defaults.datasets.bar.maxBarThickness = 32;
Chart.defaults.animation = false;
Chart.defaults.responsive = true;

window.KasMeCharts = {
    numericSeries(values) {
        return values.map((value) => {
            const numeric = Number(value);
            return Number.isFinite(numeric) ? numeric : null;
        });
    },
    cartesianScales() {
        const textColor = isDark() ? '#cbd5e1' : '#475569';
        const gridColor = isDark() ? 'rgba(71,85,105,.34)' : 'rgba(203,213,225,.65)';

        return {
            x: {
                grid: { display: false },
                ticks: { color: textColor, maxRotation: 0, autoSkip: true },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                grid: { color: gridColor },
                ticks: { color: textColor, precision: 0 },
                border: { display: false },
            },
        };
    },
};

Alpine.start();

document.querySelectorAll('form[onsubmit*="confirm("]').forEach((form) => {
    const match = form.getAttribute('onsubmit')?.match(/confirm\(['"](.+?)['"]\)/);
    if (match) {
        form.dataset.confirm = match[1];
        form.removeAttribute('onsubmit');
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
        event.preventDefault();
        window.dispatchEvent(new CustomEvent('confirm-action', {
            detail: {
                title: form.dataset.confirmTitle || 'Konfirmasi tindakan',
                message: form.dataset.confirm,
                button: form.dataset.confirmButton || 'Lanjutkan',
                form
            }
        }));
        return;
    }

    if (form.dataset.submitting === 'true') {
        event.preventDefault();
        return;
    }
    form.dataset.submitting = 'true';

    setTimeout(() => {
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            if (button.disabled) return;
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-wait');
            const loadingText = button.dataset.loadingText || 'Menyimpan…';
            button.innerHTML = `<svg class="inline -ml-1 mr-2 h-4 w-4 animate-spin text-current" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>${loadingText}</span>`;
        });
    }, 10);

    // Safety timeout in case of in-page validation or file download
    setTimeout(() => {
        form.dataset.submitting = 'false';
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
            if (button.dataset.originalHtml) {
                button.disabled = false;
                button.classList.remove('opacity-70', 'cursor-wait');
                button.innerHTML = button.dataset.originalHtml;
            }
        });
    }, 8000);
}, true);
