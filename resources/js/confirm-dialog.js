export default function confirmDialog() {
    return {
        open: false,
        title: 'Konfirmasi',
        message: '',
        confirmLabel: 'Lanjutkan',
        cancelLabel: 'Batal',
        workingLabel: 'Memproses...',
        working: false,
        onConfirm: null,
        trigger: null,

        show(detail = {}) {
            this.trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            this.title = detail.title ?? 'Konfirmasi';
            this.message = detail.message ?? '';
            this.confirmLabel = detail.confirmLabel ?? 'Lanjutkan';
            this.cancelLabel = detail.cancelLabel ?? 'Batal';
            this.workingLabel = detail.workingLabel ?? 'Memproses...';
            this.onConfirm = typeof detail.onConfirm === 'function' ? detail.onConfirm : null;
            this.working = false;
            this.open = true;

            this.$nextTick(() => this.$refs.cancel?.focus());
        },

        async confirm() {
            if (this.working || ! this.onConfirm) {
                return;
            }

            this.working = true;

            try {
                await this.onConfirm();
                this.open = false;
            } catch (error) {
                console.error('Confirm action failed', error);
            } finally {
                this.working = false;

                if (! this.open) {
                    this.$nextTick(() => this.trigger?.focus());
                }
            }
        },

        close() {
            if (! this.open || this.working) {
                return;
            }

            this.open = false;

            this.$nextTick(() => this.trigger?.focus());
        },
    };
}
