import 'leaflet/dist/leaflet.css';
import Chart from 'chart.js/auto';
import attendanceCamera from './attendance-camera';
import avatarCropper from './avatar-cropper';
import officeMapPicker from './office-map';

window.Chart = Chart;
window.attendanceCamera = attendanceCamera;
window.officeMapPicker = officeMapPicker;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('avatarCropper', avatarCropper);
});

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', ({ el }) => {
        if (el.dataset.chartRefresh === 'true' && typeof window.initSiamagChart === 'function') {
            window.initSiamagChart();
        }
    });
});
