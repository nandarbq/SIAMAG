import 'leaflet/dist/leaflet.css';
import Chart from 'chart.js/auto';
import officeMapPicker from './office-map';

window.Chart = Chart;
window.officeMapPicker = officeMapPicker;

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', ({ el }) => {
        if (el.dataset.chartRefresh === 'true' && typeof window.initSiamagChart === 'function') {
            window.initSiamagChart();
        }
    });
});
