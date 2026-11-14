/**
 * Client-Side Image Compressor (Deep Module)
 * Mengompres gambar di browser menggunakan Canvas API sebelum diunggah ke hosting gratis.
 * Menghindari limit timeout dan memory server InfinityFree.
 */

const ImageCompressor = {
    /**
     * Kompres File gambar menjadi Data URL Base64 dan Blob
     * @param {File|Blob} file 
     * @param {Object} options { maxWidth: 1600, maxHeight: 1200, quality: 0.78, mimeType: 'image/jpeg' }
     * @returns {Promise<{ dataUrl: string, blob: Blob, originalSize: number, compressedSize: number, savedPercent: number, width: number, height: number }>}
     */
    compress(file, options = {}) {
        return new Promise((resolve, reject) => {
            if (!file || !file.type.match(/image.*/)) {
                return reject(new Error('File yang dipilih bukan gambar yang valid.'));
            }

            const maxWidth  = options.maxWidth || 1600;
            const maxHeight = options.maxHeight || 1200;
            const quality   = options.quality !== undefined ? options.quality : 0.78;
            const mimeType  = options.mimeType || 'image/jpeg';

            const reader = new FileReader();
            reader.onerror = () => reject(new Error('Gagal membaca file gambar.'));
            reader.onload = (e) => {
                const img = new Image();
                img.onerror = () => reject(new Error('Gagal memuat format gambar.'));
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;

                    // Hitung rasio aspek untuk pengecilan proporsional
                    if (width > maxWidth || height > maxHeight) {
                        const ratio = Math.min(maxWidth / width, maxHeight / height);
                        width = Math.round(width * ratio);
                        height = Math.round(height * ratio);
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');

                    // Latar belakang putih jika gambar transparan diubah ke JPG
                    if (mimeType === 'image/jpeg') {
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, width, height);
                    }

                    ctx.drawImage(img, 0, 0, width, height);

                    const dataUrl = canvas.toDataURL(mimeType, quality);

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            return reject(new Error('Gagal mengompres gambar ke Blob.'));
                        }

                        const originalSize = file.size;
                        const compressedSize = blob.size;
                        const savedPercent = Math.max(0, Math.round(((originalSize - compressedSize) / originalSize) * 100));

                        resolve({
                            dataUrl,
                            blob,
                            originalSize,
                            compressedSize,
                            savedPercent,
                            width,
                            height,
                            originalFormatted: this.formatBytes(originalSize),
                            compressedFormatted: this.formatBytes(compressedSize)
                        });
                    }, mimeType, quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    },

    formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
};

window.ImageCompressor = ImageCompressor;
