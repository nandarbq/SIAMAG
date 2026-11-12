export default function attendanceCamera({ requireSelfie = true } = {}) {
    return {
        requireSelfie,
        stream: null,
        started: false,
        cameraReady: false,
        cameraError: null,
        uploading: false,
        uploadProgress: 0,
        photoUploaded: false,
        capturedUrl: null,
        capturedFile: null,

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

            const scale = Math.min(1, 1280 / Math.max(width, height));
            const outputWidth = Math.round(width * scale);
            const outputHeight = Math.round(height * scale);

            canvas.width = outputWidth;
            canvas.height = outputHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, outputWidth, outputHeight);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));

            if (! blob) {
                this.cameraError = 'Gagal mengambil foto. Coba lagi.';
                return;
            }

            if (this.capturedUrl) {
                URL.revokeObjectURL(this.capturedUrl);
            }

            this.capturedUrl = URL.createObjectURL(blob);
            this.capturedFile = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
            this.stopCamera();
            this.upload(this.capturedFile);
        },

        upload(file) {
            this.uploading = true;
            this.uploadProgress = 0;
            this.photoUploaded = false;
            this.cameraError = null;

            this.$wire.upload(
                'photo',
                file,
                () => {
                    this.uploading = false;
                    this.uploadProgress = 100;
                    this.photoUploaded = true;
                },
                () => {
                    this.uploading = false;
                    this.photoUploaded = false;
                    this.cameraError = 'Gagal mengunggah foto selfie. Coba lagi.';
                },
                (event) => {
                    this.uploadProgress = event.detail.progress;
                },
            );
        },

        retryUpload() {
            if (this.capturedFile) {
                this.upload(this.capturedFile);
            }
        },

        retake() {
            if (this.capturedUrl) {
                URL.revokeObjectURL(this.capturedUrl);
                this.capturedUrl = null;
            }

            this.capturedFile = null;
            this.photoUploaded = false;
            this.uploadProgress = 0;
            this.cameraError = null;
            this.$wire.set('photo', null);
            this.startCamera();
        },

        canSubmit() {
            return ! this.requireSelfie || this.photoUploaded;
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

            if (this.capturedUrl) {
                URL.revokeObjectURL(this.capturedUrl);
            }
        },
    };
}
