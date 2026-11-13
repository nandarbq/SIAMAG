export default function attendanceCamera({ uploadUrl } = {}) {
    return {
        stream: null,
        started: false,
        cameraReady: false,
        cameraError: null,
        saving: false,
        capturedUrl: null,

        async startCamera() {
            this.started = true;
            this.cameraError = null;
            this.cameraReady = false;

            if (! window.isSecureContext) {
                this.cameraError = 'Kamera hanya dapat digunakan melalui HTTPS atau localhost.';
                return;
            }

            if (! navigator.mediaDevices?.getUserMedia) {
                this.cameraError = 'Browser ini tidak mendukung kamera realtime. Gunakan browser terbaru.';
                return;
            }

            try {
                this.stopCamera();

                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false,
                });

                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.cameraReady = true;
            } catch (error) {
                this.cameraReady = false;
                this.cameraError = this.cameraErrorMessage(error);
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }

            if (this.$refs.video) {
                this.$refs.video.srcObject = null;
            }

            this.cameraReady = false;
        },

        async capture() {
            if (! this.cameraReady) {
                this.cameraError = 'Kamera belum siap. Tunggu sebentar lalu coba lagi.';
                return;
            }

            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const width = video.videoWidth;
            const height = video.videoHeight;

            if (! width || ! height) {
                this.cameraError = 'Gambar kamera belum tersedia. Coba lagi.';
                return;
            }

            const scale = Math.min(1, 960 / Math.max(width, height));
            const outputWidth = Math.round(width * scale);
            const outputHeight = Math.round(height * scale);

            canvas.width = outputWidth;
            canvas.height = outputHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, outputWidth, outputHeight);

            let dataUrl = canvas.toDataURL('image/jpeg', 0.78);

            if (! dataUrl.startsWith('data:image/')) {
                this.cameraError = 'Gagal mengambil foto. Coba lagi.';
                return;
            }

            if (dataUrl.length > 800000) {
                dataUrl = canvas.toDataURL('image/jpeg', 0.55);
            }

            this.capturedUrl = dataUrl;
            this.stopCamera();
            await this.savePhoto(dataUrl);
        },

        async savePhoto(dataUrl) {
            this.saving = true;
            this.cameraError = null;

            try {
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ photo: dataUrl }),
                });

                if (! response.ok) {
                    const payload = await response.json().catch(() => null);
                    this.cameraError = payload?.errors?.photo?.[0]
                        ?? payload?.message
                        ?? 'Gagal menyimpan foto selfie. Coba lagi.';

                    return;
                }

                const payload = await response.json();

                await this.$wire.set('photoToken', payload.token);
            } catch (error) {
                this.cameraError = 'Gagal menyimpan foto selfie. Periksa koneksi lalu coba lagi.';
            } finally {
                this.saving = false;
            }
        },

        retryUpload() {
            if (this.capturedUrl) {
                this.savePhoto(this.capturedUrl);
            }
        },

        retake() {
            this.capturedUrl = null;
            this.cameraError = null;
            this.$wire.set('photoToken', null);
            this.startCamera();
        },

        cameraErrorMessage(error) {
            if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                return 'Izin kamera ditolak. Aktifkan izin kamera pada browser lalu coba lagi.';
            }

            if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
                return 'Kamera tidak ditemukan. Pastikan perangkat memiliki kamera depan.';
            }

            if (error?.name === 'NotReadableError' || error?.name === 'TrackStartError') {
                return 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
            }

            return 'Kamera tidak dapat diakses. Periksa izin kamera lalu coba lagi.';
        },

        destroy() {
            this.stopCamera();
        },
    };
}
