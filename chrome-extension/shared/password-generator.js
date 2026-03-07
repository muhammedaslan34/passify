const CHARS = {
    lowercase: 'abcdefghijklmnopqrstuvwxyz',
    uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    numbers:   '0123456789',
    symbols:   '!@#$%^&*()_+-=[]{}|;:,.<>?',
};

function generate({ length = 16, lowercase = true, uppercase = true, numbers = true, symbols = true } = {}) {
    let pool = '';
    const required = [];

    if (lowercase) { pool += CHARS.lowercase; required.push(randomChar(CHARS.lowercase)); }
    if (uppercase) { pool += CHARS.uppercase; required.push(randomChar(CHARS.uppercase)); }
    if (numbers)   { pool += CHARS.numbers;   required.push(randomChar(CHARS.numbers)); }
    if (symbols)   { pool += CHARS.symbols;   required.push(randomChar(CHARS.symbols)); }

    if (!pool) return '';

    const remaining = Array.from({ length: Math.max(0, length - required.length) }, () => randomChar(pool));
    const all = [...required, ...remaining];

    for (let i = all.length - 1; i > 0; i--) {
        const j = Math.floor(getCryptoRandom() * (i + 1));
        [all[i], all[j]] = [all[j], all[i]];
    }

    return all.join('');
}

function strength(password) {
    if (!password) return { score: 0, label: 'None', color: '#e5e7eb', percent: 0 };

    let score = 0;
    if (password.length >= 8)  score++;
    if (password.length >= 12) score++;
    if (password.length >= 16) score++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;

    const clamped = Math.min(4, Math.floor(score * 4 / 6));

    const levels = [
        { label: 'Very Weak',   color: '#ef4444' },
        { label: 'Weak',        color: '#f97316' },
        { label: 'Fair',        color: '#eab308' },
        { label: 'Strong',      color: '#22c55e' },
        { label: 'Very Strong', color: '#16a34a' },
    ];

    return { score: clamped, percent: ((clamped + 1) / 5) * 100, ...levels[clamped] };
}

function randomChar(str) {
    return str[Math.floor(getCryptoRandom() * str.length)];
}

function getCryptoRandom() {
    return crypto.getRandomValues(new Uint32Array(1))[0] / (0xFFFFFFFF + 1);
}

export { generate, strength };
