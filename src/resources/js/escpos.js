const COLUMNS = { '58mm': 32, '80mm': 48 };
const ESC = 0x1b;
const GS = 0x1d;

const formatAmount = (value) => Number(value || 0).toLocaleString('id-ID');

const padCenter = (text, columns) => {
    if (text.length >= columns) {
        return text.slice(0, columns);
    }

    const totalPadding = columns - text.length;
    const left = Math.floor(totalPadding / 2);

    return ' '.repeat(left) + text;
};

const twoSides = (left, right, columns) => {
    left = String(left);
    right = String(right);
    const space = columns - left.length - right.length;

    if (space <= 0) {
        const leftMax = Math.max(0, columns - right.length);

        return left.slice(0, leftMax) + right;
    }

    return left + ' '.repeat(space) + right;
};

const wrapText = (text, columns) => {
    const words = String(text).split(/\s+/).filter(Boolean);
    const lines = [];
    let current = '';

    for (const word of words) {
        if (current.length === 0) {
            current = word.slice(0, columns);
        } else if (current.length + 1 + word.length <= columns) {
            current += ' ' + word;
        } else {
            lines.push(current);
            current = word.slice(0, columns);
        }
    }

    if (current) {
        lines.push(current);
    }

    return lines;
};

const dashedLine = (columns) => '-'.repeat(columns);

const formatDateTime = (isoString) => {
    const date = new Date(isoString);

    const pad = (value) => String(value).padStart(2, '0');

    return `${pad(date.getDate())}-${pad(date.getMonth() + 1)}-${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

export function receiptLines(transaction, { outletName = 'POS Food Court', paperWidth = '80mm' } = {}) {
    const columns = COLUMNS[paperWidth] ?? 48;
    const lines = [
        { text: outletName.toUpperCase(), align: 'center', bold: true, double: true },
    ];

    lines.push({ text: dashedLine(columns) });
    lines.push({ text: transaction.receipt_number, align: 'center' });
    lines.push({ text: formatDateTime(transaction.transaction_time), align: 'center' });

    if (transaction.order_no) {
        lines.push({ text: `No    : ${transaction.order_no}`.slice(0, columns) });
    }

    if (transaction.mode) {
        lines.push({ text: `Mode  : ${transaction.mode}`.slice(0, columns) });
    }

    if (transaction.table_no) {
        lines.push({ text: `Meja  : ${transaction.table_no}`.slice(0, columns) });
    }

    if (transaction.entry_time) {
        lines.push({ text: `Masuk : ${formatDateTime(transaction.entry_time)}`.slice(0, columns) });
    }

    if (transaction.cashier_name) {
        lines.push({ text: `Kasir : ${transaction.cashier_name}`.slice(0, columns) });
    }

    lines.push({ text: dashedLine(columns) });

    for (const item of transaction.items ?? []) {
        const nameLines = wrapText(item.menu_name, columns);
        nameLines.forEach((nameLine, index) => {
            if (index === 0) {
                lines.push({ text: nameLine });
            } else {
                lines.push({ text: nameLine });
            }
        });
        lines.push({
            text: twoSides(`  ${item.qty} x ${formatAmount(item.price)}`, formatAmount(item.subtotal), columns),
        });
    }

    lines.push({ text: dashedLine(columns) });
    lines.push({ text: twoSides('Subtotal', formatAmount(transaction.subtotal), columns) });

    if (Number(transaction.discount) > 0) {
        lines.push({ text: twoSides('Diskon', '-' + formatAmount(transaction.discount), columns) });
    }

    if (Number(transaction.tax) > 0) {
        lines.push({ text: twoSides('Pajak', formatAmount(transaction.tax), columns) });
    }

    lines.push({ text: twoSides('TOTAL', formatAmount(transaction.total), columns), bold: true });
    lines.push({ text: twoSides('Tunai', formatAmount(transaction.payment_amount), columns) });
    lines.push({ text: twoSides('Kembalian', formatAmount(transaction.change_amount), columns) });
    lines.push({ text: dashedLine(columns) });
    lines.push({ text: 'Terima kasih atas kunjungan Anda!', align: 'center' });

    return { lines, columns };
}

export function buildReceiptText(transaction, options = {}) {
    const { lines, columns } = receiptLines(transaction, options);

    return lines
        .map((line) => (line.align === 'center' ? padCenter(line.text, columns) : line.text))
        .join('\n');
}

export function buildReceiptBytes(transaction, options = {}) {
    const { lines } = receiptLines(transaction, options);
    const bytes = [ESC, 0x40];
    const encoder = new TextEncoder();
    const pushText = (text) => {
        for (const byte of encoder.encode(text)) {
            bytes.push(byte);
        }
    };

    for (const line of lines) {
        if (line.double) {
            bytes.push(GS, 0x21, 0x11);
        }

        bytes.push(ESC, 0x45, line.bold ? 1 : 0);
        bytes.push(ESC, 0x61, line.align === 'center' ? 1 : 0);
        pushText(line.text);
        bytes.push(0x0a);
        bytes.push(ESC, 0x45, 0);
        bytes.push(ESC, 0x61, 0);

        if (line.double) {
            bytes.push(GS, 0x21, 0x00);
        }
    }

    bytes.push(ESC, 0x64, 0x03);
    bytes.push(GS, 0x56, 0x42, 0x00);

    return new Uint8Array(bytes);
}
