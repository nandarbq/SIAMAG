import Chart from 'chart.js/auto';
import attendanceCamera from './attendance-camera';
import avatarCropper from './avatar-cropper';
import confirmDialog from './confirm-dialog';
import documentationPicker from './documentation-picker';

window.Chart = Chart;
window.attendanceCamera = attendanceCamera;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('avatarCropper', avatarCropper);
    window.Alpine.data('confirmDialog', confirmDialog);
    window.Alpine.data('documentationPicker', documentationPicker);

    window.Alpine.data('officeMapPicker', (options) => ({
        mapError: '',
        search: '',
        searchResults: [],
        searchLoading: false,
        searchPerformed: false,
        searchError: '',
        ready: false,
        scrollEnabled: false,

        async init() {
            try {
                const { default: createOfficeMapPicker } = await import('./office-map');

                Object.assign(this, createOfficeMapPicker(options));
                await this.init();
            } catch (error) {
                console.error(error);
                this.mapError = 'Peta gagal dimuat. Muat ulang halaman.';
            }
        },
    }));
});

document.addEventListener('livewire:init', () => {
    window.Livewire.hook('morph.updated', ({ el }) => {
        if (el.dataset.chartRefresh === 'true' && typeof window.initSiamagChart === 'function') {
            window.initSiamagChart();
        }
    });
});
