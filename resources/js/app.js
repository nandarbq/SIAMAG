import Chart from 'chart.js/auto';

window.Chart = Chart;

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', ({ el }) => {
        if (el.dataset.chartRefresh === 'true' && typeof window.initSiamagChart === 'function') {
            window.initSiamagChart();
        }
    });
});
