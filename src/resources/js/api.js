const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export async function api(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        ...options,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = data.message ?? 'Terjadi kesalahan pada server.';
        const errors = data.errors ?? null;
        const error = new Error(message);
        error.status = response.status;
        error.errors = errors;
        throw error;
    }

    return data;
}

export const formatRupiah = (value) => new Intl.NumberFormat('id-ID').format(Number(value) || 0);
