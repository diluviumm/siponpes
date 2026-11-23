import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import axios from 'axios';
window.axios = axios;

window.Alpine = Alpine;
window.Chart = Chart;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

Alpine.start();
