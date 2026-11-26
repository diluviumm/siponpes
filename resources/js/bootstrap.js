import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import axios from 'axios';
window.axios = axios;

window.Alpine = Alpine;
window.Chart = Chart;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

Alpine.start();

// Make horizontally scrollable tables keyboard reachable (WCAG 2.1.1 / best-practice).
function makeScrollRegionsFocusable() {
    document.querySelectorAll('.overflow-x-auto').forEach((el) => {
        if (!el.hasAttribute('tabindex') && el.scrollWidth > el.clientWidth + 1) {
            el.setAttribute('tabindex', '0');
            el.setAttribute('role', 'region');
            el.setAttribute('aria-label', 'Tabel dapat digeser secara horizontal');
        }
    });
}
document.addEventListener('DOMContentLoaded', makeScrollRegionsFocusable);
window.addEventListener('load', makeScrollRegionsFocusable);
new MutationObserver(makeScrollRegionsFocusable).observe(document.body, { childList: true, subtree: true });
