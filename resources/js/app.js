import './bootstrap';

document.querySelectorAll('.password-toggle').forEach((button) => {
    button.addEventListener('click', () => {
        const field = button.closest('.password-field');
        const input = field.querySelector('input');
        const show = input.type === 'password';

        input.type = show ? 'text' : 'password';
        button.classList.toggle('text-pine-700', show);
        button.classList.toggle('text-ink/50', !show);

        const eyeOpen = button.querySelector('svg:first-child');
        const eyeClosed = button.querySelector('svg:nth-child(2)');

        if (eyeOpen && eyeClosed) {
            eyeOpen.classList.toggle('hidden', show);
            eyeClosed.classList.toggle('hidden', !show);
        }
    });
});

const sidebarNav = document.querySelector('nav.sidebar-scroll');

if (sidebarNav) {
    const STORAGE_KEY = 'siponpes.sidebarScroll';
    let restoring = false;

    const saveScroll = () => {
        if (!restoring) {
            localStorage.setItem(STORAGE_KEY, String(sidebarNav.scrollTop));
        }
    };

    const restoreScroll = () => {
        restoring = true;

        const saved = localStorage.getItem(STORAGE_KEY);

        if (saved !== null) {
            sidebarNav.scrollTop = parseInt(saved, 10) || 0;
        } else {
            const active = sidebarNav.querySelector('.sidebar-link-active');

            if (active) {
                active.scrollIntoView({ block: 'nearest' });
            }
        }

        window.requestAnimationFrame(() => {
            restoring = false;
        });
    };

    document.querySelectorAll('nav.sidebar-scroll a').forEach((link) => {
        link.addEventListener('click', saveScroll, { capture: true });
    });

    sidebarNav.addEventListener('scroll', saveScroll, { passive: true });
    window.addEventListener('pagehide', saveScroll);
    window.addEventListener('load', restoreScroll);
    restoreScroll();
}

// --- Field Nomor HP: hanya angka (tolak huruf) + peringatan ---
document.querySelectorAll('.phone-input').forEach((input) => {
    input.setAttribute('inputmode', 'tel');

    let warningEl = null;
    let warningTimer = null;

    const showWarning = () => {
        if (!warningEl) {
            warningEl = document.createElement('p');
            warningEl.className = 'text-xs text-rose-600 mt-1';
            warningEl.textContent = 'Hanya angka yang boleh diisi.';
            input.parentNode.insertBefore(warningEl, input.nextSibling);
        }

        warningEl.style.display = '';
        clearTimeout(warningTimer);
        warningTimer = setTimeout(() => {
            warningEl.style.display = 'none';
        }, 2500);
    };

    input.addEventListener('input', () => {
        const previous = input.value;
        const caret = input.selectionStart;

        let cleaned = input.value.replace(/[^\d]/g, '');

        if (previous.startsWith('+')) {
            cleaned = '+' + cleaned;
        }

        if (cleaned !== input.value) {
            input.value = cleaned;

            const shift = previous.length - cleaned.length;
            const newCaret = Math.max(0, caret - shift);
            input.setSelectionRange(newCaret, newCaret);

            showWarning();
        }
    });
});

// --- Input Rupiah: tanda titik ribuan otomatis (format Indonesia) ---
const rupiahFormat = (raw) => {
    const parts = String(raw == null ? '' : raw).split(',');
    let int = parts[0].replace(/\D/g, '');
    int = int.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    if (parts.length > 1) {
        let dec = parts[1].replace(/\D/g, '').slice(0, 2);
        return dec ? int + ',' + dec : int;
    }

    return int;
};

const rupiahFromServer = (value) => {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const text = String(value).trim();
    const num = parseFloat(text);

    if (isNaN(num)) {
        return text;
    }

    const raw = num.toFixed(2)
        .replace(/0+$/, '')
        .replace(/\.$/, '')
        .replace('.', ',');

    return rupiahFormat(raw);
};

document.querySelectorAll('.rupiah-input').forEach((input) => {
    input.setAttribute('inputmode', 'numeric');
    input.value = rupiahFromServer(input.value);

    input.addEventListener('focus', () => {
        const num = parseFloat(input.value.replace(/\./g, '').replace(',', '.'));

        if (isNaN(num) || num === 0) {
            input.select();
        }
    });

    input.addEventListener('input', () => {
        input.value = rupiahFormat(input.value).replace(/^0+(?=\d)/, '');
    });

    const form = input.closest('form');

    if (form) {
        form.addEventListener('submit', () => {
            input.value = input.value.replace(/\./g, '').replace(',', '.');
        });
    }
});

// --- Konfirmasi aksi dengan modal custom SIPONPES ---
const askConfirm = function (message, options) {
    if (typeof window.__siponpesConfirm !== 'function') {
        return Promise.resolve(false);
    }

    return window.__siponpesConfirm(message, options || {});
};

const submitConfirmedForm = function (form) {
    form.dataset.confirmed = '1';
    form.requestSubmit();
    setTimeout(() => delete form.dataset.confirmed, 0);
};

document.addEventListener('submit', function (event) {
    const form = event.target;

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (!form.dataset.confirm || form.dataset.confirmed === '1') {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    askConfirm(form.dataset.confirm, {
        title: form.dataset.confirmTitle || 'Konfirmasi',
        confirmLabel: form.dataset.confirmLabel || 'Ya, Lanjutkan',
    }).then(function (ok) {
        if (ok) {
            submitConfirmedForm(form);
        }
    });
}, true);

window.siponpesConfirm = function (event, message, options) {
    // Submit kedua berasal dari submitConfirmedForm setelah konfirmasi diterima.
    // Tanpa penjaga ini, handler inline onsubmit membuka modal lagi dan
    // membatalkan submit — pengguna melihat tombol "tidak ada aksi".
    if (event.target instanceof HTMLFormElement && event.target.dataset.confirmed === '1') {
        return true;
    }

    event.preventDefault();
    event.stopPropagation();

    askConfirm(message, options || {}).then(function (ok) {
        if (ok) {
            submitConfirmedForm(event.target);
        }
    });

    return false;
};
