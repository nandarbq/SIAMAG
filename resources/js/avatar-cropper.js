import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

export default function avatarCropper() {
    return {
        open: false,
        cropper: null,
        objectUrl: null,
        zoomLevel: 1,
        uploading: false,
        progress: 0,
        error: '',
        maxFileSize: 10 * 1024 * 1024,

        chooseFile() {
            this.$refs.input?.click();
        },

        onFileSelected(event) {
            const file = event.target.files?.[0];

            if (!file) {
                return;
            }

            this.error = '';

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                this.error = 'Format foto harus JPG, PNG, atau WebP.';
                this.resetInput();

                return;
            }

            if (file.size > this.maxFileSize) {
                this.error = 'Ukuran foto maksimal 10MB sebelum dipotong.';
                this.resetInput();

                return;
            }

            this.destroyCropper();
            this.objectUrl = URL.createObjectURL(file);
            this.zoomLevel = 1;
            this.open = true;

            this.$nextTick(() => this.initCropper());
        },

        initCropper() {
            const image = this.$refs.image;

            if (!image || !this.objectUrl) {
                return;
            }

            image.onload = () => {
                if (!this.open || !this.objectUrl) {
                    return;
                }

                this.cropper = new Cropper(image, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.9,
                    background: false,
                    center: false,
                    guides: false,
                    highlight: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    toggleDragModeOnDblclick: false,
                    responsive: true,
                    checkOrientation: true,
                    zoomOnWheel: true,
                });
            };

            image.onerror = () => {
                this.error = 'Foto tidak dapat dibaca. Coba file lain.';
            };

            image.src = this.objectUrl;
        },

        zoom(event) {
            const next = Number.parseFloat(event.target.value);
            const delta = next - this.zoomLevel;

            this.zoomLevel = next;

            if (this.cropper && delta !== 0) {
                this.cropper.zoom(delta);
            }
        },

        async save() {
            if (!this.cropper || this.uploading) {
                return;
            }

            this.uploading = true;
            this.progress = 0;
            this.error = '';

            try {
                const canvas = this.cropper.getCroppedCanvas({
                    width: 512,
                    height: 512,
                    fillColor: '#ffffff',
                    imageSmoothingQuality: 'high',
                });

                if (!canvas) {
                    throw new Error('Gagal memproses hasil crop.');
                }

                const blob = await new Promise((resolve, reject) => {
                    canvas.toBlob(
                        (result) => result ? resolve(result) : reject(new Error('Gagal memproses hasil crop.')),
                        'image/jpeg',
                        0.9,
                    );
                });

                const file = new File([blob], 'avatar.jpg', {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });

                await new Promise((resolve, reject) => {
                    this.$wire.upload(
                        'photo',
                        file,
                        resolve,
                        () => reject(new Error('Upload foto gagal. Coba lagi.')),
                        (event) => {
                            this.progress = event.detail.progress;
                        },
                    );
                });

                await this.$wire.updatePhoto();
                this.uploading = false;
            } catch (error) {
                this.uploading = false;
                this.error = error?.message || 'Gagal menyimpan foto.';
            }
        },

        close() {
            if (this.uploading) {
                return;
            }

            this.open = false;
            this.error = '';
            this.progress = 0;
            this.destroyCropper();
            this.resetInput();
        },

        destroyCropper() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }

            if (this.objectUrl) {
                URL.revokeObjectURL(this.objectUrl);
                this.objectUrl = null;
            }

            if (this.$refs.image) {
                this.$refs.image.removeAttribute('src');
            }
        },

        resetInput() {
            if (this.$refs.input) {
                this.$refs.input.value = '';
            }
        },

        destroy() {
            this.destroyCropper();
            document.documentElement.classList.remove('overflow-hidden');
        },
    };
}
