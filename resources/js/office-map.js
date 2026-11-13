import L from 'leaflet';

export default function officeMapPicker({ lat, lng }) {
    return {
        map: null,
        marker: null,
        lat: Number(lat) || 0,
        lng: Number(lng) || 0,

        init() {
            this.map = L.map(this.$refs.map, {
                scrollWheelZoom: true,
            }).setView([this.lat, this.lng], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(this.map);

            this.marker = L.marker([this.lat, this.lng], {
                draggable: true,
                icon: L.divIcon({
                    className: '',
                    html: '<span style="display:block;width:18px;height:18px;border-radius:9999px;background:#0f766e;border:3px solid #fff;box-shadow:0 2px 10px rgba(15,23,42,.35)"></span>',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9],
                }),
            }).addTo(this.map);

            this.marker.on('dragend', () => {
                const point = this.marker.getLatLng();
                this.applyPoint(point.lat, point.lng, true);
            });

            this.map.on('click', (event) => {
                this.applyPoint(event.latlng.lat, event.latlng.lng, true);
            });

            this.$nextTick(() => this.map.invalidateSize());
        },

        applyPoint(lat, lng, updateWire = false) {
            this.lat = Number(lat);
            this.lng = Number(lng);

            this.marker?.setLatLng([this.lat, this.lng]);

            if (updateWire) {
                this.$wire.set('office_lat', this.lat.toFixed(7));
                this.$wire.set('office_lng', this.lng.toFixed(7));
            }
        },

        syncFromInputs() {
            const lat = Number.parseFloat(this.$refs.officeLat?.value);
            const lng = Number.parseFloat(this.$refs.officeLng?.value);

            if (! Number.isFinite(lat) || ! Number.isFinite(lng)) {
                return;
            }

            this.applyPoint(lat, lng);
            this.map?.panTo([lat, lng]);
        },

        locateDevice() {
            if (! navigator.geolocation) {
                alert('Browser tidak mendukung geolocation.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    this.applyPoint(lat, lng, true);
                    this.map?.setView([lat, lng], 17);
                },
                () => alert('Gagal mendeteksi lokasi. Pastikan izin lokasi aktif.'),
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
            );
        },

        destroy() {
            this.map?.remove();
        },
    };
}
