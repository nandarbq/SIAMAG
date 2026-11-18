import { Map, Marker, NavigationControl, ScaleControl, setWorkerUrl } from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import 'maplibre-gl/dist/maplibre-gl.css';

setWorkerUrl(workerUrl);

const DEFAULT_LAT = -8.2192;
const DEFAULT_LNG = 114.3692;
const DEFAULT_RADIUS = 150;
const STYLE_URL = 'https://tiles.openfreemap.org/styles/bright';
const SEARCH_ENDPOINT = 'https://photon.komoot.io/api/';
const INDONESIA_BBOX = '94.97,-11.01,141.02,6.08';

function toFiniteNumber(value, fallback) {
    const parsed = Number.parseFloat(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}

function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
}

function searchResultFromFeature(feature, index) {
    const properties = feature.properties ?? {};
    const coordinates = feature.geometry?.coordinates ?? [];
    const street = [properties.street, properties.housenumber].filter(Boolean).join(' ');
    const detail = [...new Set([
        street,
        properties.district,
        properties.city,
        properties.state,
        properties.country,
    ].filter(Boolean))].filter((part) => part !== properties.name).join(', ');

    return {
        id: `${properties.osm_type ?? 'osm'}-${properties.osm_id ?? index}-${index}`,
        name: properties.name || street || properties.city || properties.state || 'Lokasi',
        detail,
        lng: Number(coordinates[0]),
        lat: Number(coordinates[1]),
    };
}

export default function officeMapPicker({ lat, lng, radius = DEFAULT_RADIUS } = {}) {
    return {
        map: null,
        marker: null,
        lat: toFiniteNumber(lat, DEFAULT_LAT),
        lng: toFiniteNumber(lng, DEFAULT_LNG),
        radius: clamp(toFiniteNumber(radius, DEFAULT_RADIUS), 10, 5000),
        search: '',
        searchResults: [],
        searchLoading: false,
        searchPerformed: false,
        searchError: '',
        mapError: '',
        ready: false,
        searchController: null,
        loadTimer: null,
        resizeObserver: null,

        init() {
            this.map = new Map({
                container: this.$refs.map,
                style: STYLE_URL,
                center: [this.lng, this.lat],
                zoom: 16,
                attributionControl: true,
            });

            this.map.addControl(new NavigationControl({ showCompass: true }), 'top-right');
            this.map.addControl(new ScaleControl({ unit: 'metric', maxWidth: 120 }), 'bottom-left');

            this.marker = new Marker({
                color: '#0f766e',
                draggable: true,
            })
                .setLngLat([this.lng, this.lat])
                .addTo(this.map);

            this.marker.on('dragend', () => {
                const point = this.marker.getLngLat();
                this.applyPoint(point.lat, point.lng, true);
            });

            this.map.on('click', (event) => {
                this.applyPoint(event.lngLat.lat, event.lngLat.lng, true);
            });

            this.map.on('load', () => {
                this.ready = true;
                this.mapError = '';
                window.clearTimeout(this.loadTimer);
                this.addRadiusLayer();
                this.updateRadiusLayer();
                this.map.resize();
            });

            this.map.on('error', (event) => {
                console.error('Office map error', event.error);
            });

            this.loadTimer = window.setTimeout(() => {
                if (!this.ready) {
                    this.mapError = 'Peta lambat dimuat. Periksa koneksi internet.';
                }
            }, 12000);

            this.resizeObserver = new ResizeObserver(() => this.map?.resize());
            this.resizeObserver.observe(this.$refs.map);

            this.$nextTick(() => this.map?.resize());
        },

        addRadiusLayer() {
            if (!this.map || this.map.getSource('office-radius')) {
                return;
            }

            this.map.addSource('office-radius', {
                type: 'geojson',
                data: this.circleGeoJSON(),
            });

            this.map.addLayer({
                id: 'office-radius-fill',
                type: 'fill',
                source: 'office-radius',
                paint: {
                    'fill-color': '#0f766e',
                    'fill-opacity': 0.14,
                },
            });

            this.map.addLayer({
                id: 'office-radius-line',
                type: 'line',
                source: 'office-radius',
                paint: {
                    'line-color': '#0f766e',
                    'line-width': 2,
                    'line-opacity': 0.9,
                },
            });
        },

        updateRadiusLayer() {
            const source = this.map?.getSource('office-radius');

            if (source && typeof source.setData === 'function') {
                source.setData(this.circleGeoJSON());
            }
        },

        circleGeoJSON() {
            const points = [];
            const steps = 96;
            const earthRadius = 6371000;
            const angularDistance = this.radius / earthRadius;
            const latRad = this.lat * Math.PI / 180;
            const lngRad = this.lng * Math.PI / 180;

            for (let i = 0; i <= steps; i++) {
                const bearing = (i / steps) * 2 * Math.PI;
                const pointLat = Math.asin(
                    Math.sin(latRad) * Math.cos(angularDistance)
                    + Math.cos(latRad) * Math.sin(angularDistance) * Math.cos(bearing),
                );
                const pointLng = lngRad + Math.atan2(
                    Math.sin(bearing) * Math.sin(angularDistance) * Math.cos(latRad),
                    Math.cos(angularDistance) - Math.sin(latRad) * Math.sin(pointLat),
                );

                points.push([pointLng * 180 / Math.PI, pointLat * 180 / Math.PI]);
            }

            return {
                type: 'Feature',
                properties: {},
                geometry: {
                    type: 'Polygon',
                    coordinates: [points],
                },
            };
        },

        applyPoint(lat, lng, updateWire = false) {
            const nextLat = Number(lat);
            const nextLng = Number(lng);

            if (!Number.isFinite(nextLat) || !Number.isFinite(nextLng)) {
                return;
            }

            this.lat = nextLat;
            this.lng = nextLng;

            this.marker?.setLngLat([this.lng, this.lat]);
            this.updateRadiusLayer();

            if (updateWire) {
                this.$wire.set('office_lat', this.lat.toFixed(7));
                this.$wire.set('office_lng', this.lng.toFixed(7));
            }
        },

        syncFromInputs() {
            const lat = Number.parseFloat(this.$refs.officeLat?.value);
            const lng = Number.parseFloat(this.$refs.officeLng?.value);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            this.applyPoint(lat, lng);
            this.map?.panTo([lng, lat]);
        },

        setRadius(value) {
            const radius = Number.parseFloat(value);

            if (!Number.isFinite(radius)) {
                return;
            }

            this.radius = clamp(radius, 10, 5000);
            this.updateRadiusLayer();
        },

        locateDevice() {
            if (!navigator.geolocation) {
                this.mapError = 'Browser tidak mendukung geolocation.';
                return;
            }

            this.mapError = '';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    this.applyPoint(lat, lng, true);
                    this.map?.flyTo({ center: [lng, lat], zoom: 17 });
                },
                () => {
                    this.mapError = 'Gagal mendeteksi lokasi. Pastikan izin lokasi aktif.';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
            );
        },

        async searchPlaces() {
            const query = this.search.trim();

            if (query.length < 3) {
                this.searchController?.abort();
                this.searchController = null;
                this.searchResults = [];
                this.searchPerformed = false;
                this.searchLoading = false;
                this.searchError = '';

                return;
            }

            this.searchController?.abort();

            const controller = new AbortController();
            this.searchController = controller;
            this.searchLoading = true;
            this.searchPerformed = false;
            this.searchError = '';

            const params = new URLSearchParams({
                q: query,
                limit: '6',
                bbox: INDONESIA_BBOX,
                lat: String(this.lat),
                lon: String(this.lng),
                zoom: '12',
            });

            try {
                const response = await fetch(`${SEARCH_ENDPOINT}?${params.toString()}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error('Pencarian gagal.');
                }

                const payload = await response.json();

                this.searchResults = (payload.features ?? [])
                    .map(searchResultFromFeature)
                    .filter((result) => Number.isFinite(result.lat) && Number.isFinite(result.lng));
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.searchResults = [];
                    this.searchError = 'Pencarian gagal. Coba lagi.';
                }
            } finally {
                if (this.searchController === controller) {
                    this.searchLoading = false;
                    this.searchPerformed = true;
                    this.searchController = null;
                }
            }
        },

        selectSearchResult(result) {
            this.applyPoint(result.lat, result.lng, true);
            this.map?.flyTo({ center: [result.lng, result.lat], zoom: 17, essential: true });
            this.search = result.name;
            this.searchResults = [];
            this.searchPerformed = false;
            this.searchError = '';
        },

        clearSearch() {
            this.searchController?.abort();
            this.searchController = null;
            this.search = '';
            this.searchResults = [];
            this.searchPerformed = false;
            this.searchLoading = false;
            this.searchError = '';
        },

        destroy() {
            window.clearTimeout(this.loadTimer);
            this.searchController?.abort();
            this.resizeObserver?.disconnect();
            this.marker?.remove();
            this.map?.remove();
            this.map = null;
            this.marker = null;
        },
    };
}
