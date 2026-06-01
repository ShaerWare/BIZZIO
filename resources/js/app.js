import './bootstrap';

import Alpine from 'alpinejs';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

window.Alpine = Alpine;

/**
 * #146: переиспользуемый кроп аватаров (зум + обрезка) на базе Cropper.js.
 * Видимый input выбирает файл; обрезанное изображение кладётся в скрытый
 * input с нужным name, который и уходит на сервер вместе с формой.
 */
Alpine.data('avatarCropper', (opts = {}) => ({
    open: false,
    cropper: null,
    imgSrc: null,
    aspectRatio: opts.aspectRatio ?? 1,

    pick(event) {
        const file = event.target.files[0];
        event.target.value = ''; // позволяем выбрать тот же файл повторно
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            this.imgSrc = e.target.result;
            this.open = true;
            this.$nextTick(() => {
                if (this.cropper) {
                    this.cropper.destroy();
                }
                this.cropper = new Cropper(this.$refs.image, {
                    aspectRatio: this.aspectRatio,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                });
            });
        };
        reader.readAsDataURL(file);
    },

    apply() {
        if (!this.cropper) {
            return;
        }
        this.cropper
            .getCroppedCanvas({ width: 512, height: 512, imageSmoothingQuality: 'high' })
            .toBlob(
                (blob) => {
                    const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.fileInput.files = dt.files;
                    if (this.$refs.preview) {
                        this.$refs.preview.src = URL.createObjectURL(blob);
                        this.$refs.preview.style.display = '';
                    }
                    this.close();
                },
                'image/jpeg',
                0.9
            );
    },

    close() {
        this.open = false;
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
    },
}));

Alpine.start();
