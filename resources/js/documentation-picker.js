export default function documentationPicker(options = {}) {
    return {
        inputName: 'photo',
        existingUrl: null,
        open: false,
        view: 'menu',
        facingMode: 'environment',
        cameraReady: false,
        multipleCameras: true,
        error: '',
        uploading: false,
        progress: 0,
        stream: null,
        capturedUrl: null,
        selectedUrl: null,
        selectedName: '',
        selectedFile: null,
        objectUrl: null,
        ...options,

        get cameraLabel() {
            return this.facingMode === 'user' ? 'Kamera Depan' : 'Kamera Belakang';
        },

        get hasSelection() {
            return Boolean(this.selectedUrl || this.existingUrl);
        },

        openPicker() {
            if (this.uploading) {
                return;
            }

            this.open = true;
            this.view = 'menu';
            this.error = '';
            this.stopCamera();
        },

        chooseFile() {
            this.$refs.fileInput?.click();
        },

        openSystemCamera() {
            this.error = '';
            this.$refs.systemCameraInput?.click();
        },

        onFileSelected(event) {
            const file = event.target.files?.[0];
            event.target.value = '';

            if (!file) {
                return;
            }

            const validationError = this.validateFile(file);

            if (validationError) {
                this.error = validationError;
                return;
            }

            this.error = '';
            this.capturedUrl = null;
            this.selectedFile = file;
            this.selectedName = file.name;
            this.replaceObjectUrl(URL.createObjectURL(file));
            this.selectedUrl = this.objectUrl;
            this.view = 'preview';
            this.stopCamera();
        },

        async startCamera(facingMode = this.facingMode) {
            this.open = true;
            this.view = 'camera';
            this.error = '';
            this.cameraReady = false;
            this.stopCamera();

            if (! window.isSecureContext) {
                this.error = 'Kamera hanya dapat digunakan melalui HTTPS atau localhost. Gunakan galeri atau kamera sistem.';
                return;
            }

            if (! navigator.mediaDevices?.getUserMedia) {
                this.error = 'Browser ini tidak mendukung kamera realtime. Gunakan galeri atau kamera sistem.';
                return;
            }

            this.facingMode = facingMode;

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: {
                        facingMode: { ideal: facingMode },
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                    },
                });

                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.cameraReady = true;
                await this.detectCameras();
            } catch (error) {
                if (error?.name === 'OverconstrainedError') {
                    await this.startDefaultCamera();
                    return;
                }

                this.error = this.cameraErrorMessage(error);
            }
        },

        async startDefaultCamera() {
            this.stopCamera();

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: true,
                });

                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.cameraReady = true;
                await this.detectCameras();
            } catch (error) {
                this.error = this.cameraErrorMessage(error);
            }
        },

        async detectCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.multipleCameras = devices.filter((device) => device.kind === 'videoinput').length > 1;
            } catch {
                this.multipleCameras = true;
            }
        },

        async flipCamera() {
            if (! this.multipleCameras || this.uploading) {
                return;
            }

            await this.startCamera(this.facingMode === 'environment' ? 'user' : 'environment');
        },

        capture() {
            if (! this.cameraReady) {
                this.error = 'Kamera belum siap. Tunggu sebentar lalu coba lagi.';
                return;
            }

            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            const width = video.videoWidth;
            const height = video.videoHeight;

            if (! width || ! height) {
                this.error = 'Gambar kamera belum tersedia. Coba lagi.';
                return;
            }

            const scale = Math.min(1, 1280 / Math.max(width, height));
            const outputWidth = Math.round(width * scale);
            const outputHeight = Math.round(height * scale);
            const context = canvas.getContext('2d');

            canvas.width = outputWidth;
            canvas.height = outputHeight;
            context.save();

            if (this.facingMode === 'user') {
                context.translate(outputWidth, 0);
                context.scale(-1, 1);
            }

            context.drawImage(video, 0, 0, outputWidth, outputHeight);
            context.restore();

            let dataUrl = canvas.toDataURL('image/jpeg', 0.82);

            for (const quality of [0.65, 0.5, 0.38]) {
                if (dataUrl.length <= 1500000) {
                    break;
                }

                dataUrl = canvas.toDataURL('image/jpeg', quality);
            }

            if (! dataUrl.startsWith('data:image/')) {
                this.error = 'Gagal mengambil gambar. Coba lagi.';
                return;
            }

            this.error = '';
            this.capturedUrl = dataUrl;
            this.selectedUrl = dataUrl;
            this.selectedName = `dokumentasi-${Date.now()}.jpg`;
            this.selectedFile = null;
            this.view = 'preview';
            this.stopCamera();
        },

        async retake() {
            this.capturedUrl = null;
            this.selectedFile = null;
            this.selectedUrl = null;
            this.selectedName = '';

            if (this.objectUrl && ! this.objectUrl.startsWith('data:')) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }

            this.error = '';
            await this.startCamera();
        },

        async usePhoto() {
            if (! this.selectedUrl || this.uploading) {
                return;
            }

            const file = this.selectedFile ?? await this.fileFromDataUrl(this.capturedUrl, this.selectedName);
            const validationError = this.validateFile(file);

            if (validationError) {
                this.error = validationError;
                return;
            }

            this.uploading = true;
            this.progress = 0;
            this.error = '';

            try {
                await new Promise((resolve, reject) => {
                    this.$wire.upload(
                        this.inputName,
                        file,
                        resolve,
                        () => reject(new Error('upload failed')),
                        (event) => {
                            this.progress = event.detail.progress;
                        },
                    );
                });

                this.open = false;
                this.view = 'menu';
                this.stopCamera();
            } catch {
                this.error = 'Dokumentasi gagal diunggah. Periksa koneksi lalu coba lagi.';
            } finally {
                this.uploading = false;
            }
        },

        async fileFromDataUrl(dataUrl, name) {
            const response = await fetch(dataUrl);
            const blob = await response.blob();

            return new File([blob], name, {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });
        },

        validateFile(file) {
            if (! ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                return 'Format dokumentasi harus JPG, PNG, atau WEBP.';
            }

            if (file.size > 4 * 1024 * 1024) {
                return 'Ukuran dokumentasi maksimal 4 MB.';
            }

            return null;
        },

        replaceObjectUrl(url) {
            if (this.objectUrl && this.objectUrl !== url) {
                URL.revokeObjectURL(this.objectUrl);
            }

            this.objectUrl = url;
        },

        close() {
            if (this.uploading) {
                return;
            }

            this.open = false;
            this.view = 'menu';
            this.error = '';
            this.stopCamera();
            this.resetInputs();
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

        resetInputs() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }

            if (this.$refs.systemCameraInput) {
                this.$refs.systemCameraInput.value = '';
            }
        },

        cameraErrorMessage(error) {
            if (error?.name === 'NotAllowedError' || error?.name === 'SecurityError') {
                return 'Izin kamera ditolak. Aktifkan izin kamera pada browser lalu coba lagi.';
            }

            if (error?.name === 'NotFoundError' || error?.name === 'DevicesNotFoundError') {
                return 'Kamera tidak ditemukan. Gunakan galeri atau kamera sistem.';
            }

            if (error?.name === 'NotReadableError' || error?.name === 'TrackStartError') {
                return 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
            }

            return 'Kamera tidak dapat diakses. Gunakan galeri atau kamera sistem.';
        },

        destroy() {
            this.stopCamera();

            if (this.objectUrl && ! this.objectUrl.startsWith('data:')) {
                URL.revokeObjectURL(this.objectUrl);
            }
        },
    };
}
