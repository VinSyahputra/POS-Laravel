const COLUMNS = { '58mm': 32, '80mm': 48 };
const ESC = 0x1b;
const GS = 0x1d;

const TEMPLATE_LABELS = {
    PASTRY_BAKERY: 'Pastry & Bakery',
    FOODCOURT: 'Foodcourt',
    CAFE_1912: 'Cafe 1912',
};

const formatAmount = (value) => Number(value || 0).toLocaleString('id-ID');

const padCenter = (text, columns) => {
    if (text.length >= columns) {
        return text.slice(0, columns);
    }

    const totalPadding = columns - text.length;
    const left = Math.floor(totalPadding / 2);

    return ' '.repeat(left) + text;
};

const padRightAlign = (text, columns) => {
    if (text.length >= columns) {
        return text.slice(0, columns);
    }

    return ' '.repeat(columns - text.length) + text;
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

// Many cheap ESC/POS clone printers silently strip leading space characters,
// so the totals block indent is sent as a real tab stop (see buildReceiptBytes)
// rather than baked-in spaces. This only reserves the column budget for it.
const TOTALS_INDENT_WIDTH = 6;

const indentedTwoSides = (left, right, columns) => {
    const available = Math.max(0, columns - TOTALS_INDENT_WIDTH);

    return twoSides(left, right, available).slice(0, available);
};

const field = (label, value, columns) => `${label.padEnd(10)} : ${value}`.slice(0, columns);

const formatDateTime = (isoString) => {
    const date = new Date(isoString);

    const pad = (value) => String(value).padStart(2, '0');

    return `${pad(date.getDate())}-${pad(date.getMonth() + 1)}-${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

// Each template below builds its own line array from scratch (some duplication
// vs. one shared builder is intentional) so tweaking one template's layout can
// never leak into the other two.

function foodcourtLines(transaction, columns, headerName) {
    const lines = [
        { text: headerName, align: 'center', bold: false, double: false },
    ];

    lines.push({ text: dashedLine(columns) });
    lines.push({ feed: 4 });

    lines.push({ text: field('No', transaction.generated_no || transaction.order_no || '-', columns), bold: false });
    lines.push({ text: field('Tanggal', transaction.transaction_time ? formatDateTime(transaction.transaction_time) : '-', columns), bold: false });
    lines.push({ text: field('Jam Masuk', transaction.entry_time ? formatDateTime(transaction.entry_time) : '-', columns), bold: false });
    lines.push({ text: field('No Meja', transaction.table_no || '-', columns), bold: false });
    lines.push({ text: field('Mode', transaction.mode || '-', columns), bold: false });
    lines.push({ text: field('Kasir', transaction.cashier_name || '-', columns), bold: false });

    lines.push({ text: dashedLine(columns) });
    lines.push({ feed: 4 });

    const items = transaction.items ?? [];

    for (const item of items) {
        const nameLines = wrapText(item.menu_name, columns);
        nameLines.forEach((nameLine) => lines.push({ text: nameLine, bold: true }));
        lines.push({
            text: twoSides(`${item.qty}x   @${formatAmount(item.price)}`, formatAmount(item.subtotal), columns),
        });
    }

    lines.push({ text: dashedLine(columns) });
    lines.push({ feed: 4 });

    const itemCount = items.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
    lines.push({ text: `${itemCount} item` });

    const paymentLabel = String(transaction.payment_method || 'CASH').toUpperCase();
    const totalsRows = [{ label: 'Subtotal', value: formatAmount(transaction.subtotal) }];

    if (Number(transaction.discount) > 0) {
        totalsRows.push({ label: 'Diskon', value: '-' + formatAmount(transaction.discount) });
    }

    if (Number(transaction.tax) > 0) {
        totalsRows.push({ label: 'Pajak', value: formatAmount(transaction.tax) });
    }

    totalsRows.push({ label: 'Grand Total', value: formatAmount(transaction.total), bold: true, tall: true });
    totalsRows.push({ label: paymentLabel, value: formatAmount(transaction.payment_amount) });

    if (Number(transaction.change_amount) > 0) {
        totalsRows.push({ label: 'Kembalian', value: formatAmount(transaction.change_amount), separatorBefore: true });
    }

    // Right-align every label to the same width so the ":" lines up down the column.
    const labelWidth = Math.max(...totalsRows.map((row) => row.label.length));

    totalsRows.forEach((row) => {
        if (row.separatorBefore) {
            lines.push({ text: '-'.repeat(22), align: 'right' });
        }

        lines.push({
            text: indentedTwoSides(`${row.label.padStart(labelWidth)} :`, row.value, columns),
            indent: true,
            bold: row.bold,
            tall: row.tall,
        });
    });

    lines.push({ text: dashedLine(columns) });
    lines.push({ text: '- Thank You -', align: 'center' });

    return lines;
}

function pastryBakeryLines(transaction, columns, headerName) {
    const lines = [
        { text: headerName, align: 'center', bold: false, double: false },
    ];

    lines.push({ text: dashedLine(columns) });

    if (transaction.generated_no || transaction.order_no) {
        lines.push({ text: field('No', transaction.generated_no || transaction.order_no, columns) });
    }

    if (transaction.transaction_time) {
        lines.push({ text: field('Tanggal', formatDateTime(transaction.transaction_time), columns) });
    }

    if (transaction.entry_time) {
        lines.push({ text: field('Jam Masuk', formatDateTime(transaction.entry_time), columns) });
    }

    if (transaction.table_no) {
        lines.push({ text: field('No Meja', transaction.table_no, columns) });
    }

    if (transaction.mode) {
        lines.push({ text: field('Mode', transaction.mode, columns) });
    }

    if (transaction.cashier_name) {
        lines.push({ text: field('Kasir', transaction.cashier_name, columns) });
    }

    lines.push({ text: dashedLine(columns) });

    const items = transaction.items ?? [];

    for (const item of items) {
        const nameLines = wrapText(item.menu_name, columns);
        nameLines.forEach((nameLine) => lines.push({ text: nameLine, bold: true }));
        lines.push({
            text: twoSides(`${item.qty}x @${formatAmount(item.price)}`, formatAmount(item.subtotal), columns),
        });
    }

    lines.push({ text: dashedLine(columns) });

    const itemCount = items.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
    lines.push({ text: `${itemCount} item` });

    const paymentLabel = String(transaction.payment_method || 'CASH').toUpperCase();
    const totalsRows = [{ label: 'Subtotal', value: formatAmount(transaction.subtotal) }];

    if (Number(transaction.discount) > 0) {
        totalsRows.push({ label: 'Diskon', value: '-' + formatAmount(transaction.discount) });
    }

    if (Number(transaction.tax) > 0) {
        totalsRows.push({ label: 'Pajak', value: formatAmount(transaction.tax) });
    }

    totalsRows.push({ label: 'Grand Total', value: formatAmount(transaction.total), bold: true, tall: true });
    totalsRows.push({ label: paymentLabel, value: formatAmount(transaction.payment_amount) });

    if (Number(transaction.change_amount) > 0) {
        totalsRows.push({ label: 'Kembalian', value: formatAmount(transaction.change_amount), separatorBefore: true });
    }

    // Right-align every label to the same width so the ":" lines up down the column.
    const labelWidth = Math.max(...totalsRows.map((row) => row.label.length));

    totalsRows.forEach((row) => {
        if (row.separatorBefore) {
            lines.push({ text: '-'.repeat(22), align: 'right' });
        }

        lines.push({
            text: indentedTwoSides(`${row.label.padStart(labelWidth)} :`, row.value, columns),
            indent: true,
            bold: row.bold,
            tall: row.tall,
        });
    });

    lines.push({ text: dashedLine(columns) });
    lines.push({ text: '- Thank You -', align: 'center' });

    return lines;
}

function cafe1912Lines(transaction, columns, headerName) {
    const lines = [
        { text: headerName, align: 'center', bold: false, double: false },
    ];

    lines.push({ text: dashedLine(columns) });

    if (transaction.generated_no || transaction.order_no) {
        lines.push({ text: field('No', transaction.generated_no || transaction.order_no, columns) });
    }

    if (transaction.transaction_time) {
        lines.push({ text: field('Tanggal', formatDateTime(transaction.transaction_time), columns) });
    }

    if (transaction.entry_time) {
        lines.push({ text: field('Jam Masuk', formatDateTime(transaction.entry_time), columns) });
    }

    if (transaction.table_no) {
        lines.push({ text: field('No Meja', transaction.table_no, columns) });
    }

    if (transaction.mode) {
        lines.push({ text: field('Mode', transaction.mode, columns) });
    }

    if (transaction.cashier_name) {
        lines.push({ text: field('Kasir', transaction.cashier_name, columns) });
    }

    lines.push({ text: dashedLine(columns) });

    const items = transaction.items ?? [];

    for (const item of items) {
        const nameLines = wrapText(item.menu_name, columns);
        nameLines.forEach((nameLine) => lines.push({ text: nameLine, bold: true }));
        lines.push({
            text: twoSides(`${item.qty}x @${formatAmount(item.price)}`, formatAmount(item.subtotal), columns),
        });
    }

    lines.push({ text: dashedLine(columns) });

    const itemCount = items.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
    lines.push({ text: `${itemCount} item` });

    const paymentLabel = String(transaction.payment_method || 'CASH').toUpperCase();
    const totalsRows = [{ label: 'Subtotal', value: formatAmount(transaction.subtotal) }];

    if (Number(transaction.discount) > 0) {
        totalsRows.push({ label: 'Diskon', value: '-' + formatAmount(transaction.discount) });
    }

    if (Number(transaction.tax) > 0) {
        totalsRows.push({ label: 'Pajak', value: formatAmount(transaction.tax) });
    }

    totalsRows.push({ label: 'Grand Total', value: formatAmount(transaction.total), bold: true, tall: true });
    totalsRows.push({ label: paymentLabel, value: formatAmount(transaction.payment_amount) });

    if (Number(transaction.change_amount) > 0) {
        totalsRows.push({ label: 'Kembalian', value: formatAmount(transaction.change_amount), separatorBefore: true });
    }

    // Right-align every label to the same width so the ":" lines up down the column.
    const labelWidth = Math.max(...totalsRows.map((row) => row.label.length));

    totalsRows.forEach((row) => {
        if (row.separatorBefore) {
            lines.push({ text: '-'.repeat(22), align: 'right' });
        }

        lines.push({
            text: indentedTwoSides(`${row.label.padStart(labelWidth)} :`, row.value, columns),
            indent: true,
            bold: row.bold,
            tall: row.tall,
        });
    });

    lines.push({ text: dashedLine(columns) });
    lines.push({ text: '- Thank You -', align: 'center' });

    const queueNo = transaction.order_no ? String(transaction.order_no).slice(-2).padStart(2, '0') : '-';

    lines.push({ text: 'Nomor antrian', align: 'center', bold: true, tall: true });
    lines.push({ text: queueNo, align: 'center', bold: true, size: 3 });
    lines.push({ text: 'Tunggu nomor kamu dipanggil', align: 'center' });

    return lines;
}

const TEMPLATE_BUILDERS = {
    FOODCOURT: foodcourtLines,
    PASTRY_BAKERY: pastryBakeryLines,
    CAFE_1912: cafe1912Lines,
};

export function receiptLines(transaction, { outletName = 'POS Food Court', paperWidth = '80mm' } = {}) {
    const columns = COLUMNS[paperWidth] ?? 48;
    const headerName = TEMPLATE_LABELS[transaction.template] ?? outletName;
    const builder = TEMPLATE_BUILDERS[transaction.template] ?? pastryBakeryLines;

    return { lines: builder(transaction, columns, headerName), columns };
}

export function buildReceiptText(transaction, options = {}) {
    const { lines, columns } = receiptLines(transaction, options);

    return lines
        .filter((line) => line.feed === undefined)
        .map((line) => {
            const text = line.indent ? ' '.repeat(TOTALS_INDENT_WIDTH) + line.text : line.text;

            if (line.align === 'center') {
                return padCenter(text, columns);
            }

            if (line.align === 'right') {
                return padRightAlign(text, columns);
            }

            return text;
        })
        .join('\n');
}

// Foodcourt-only: extra paper feed appended after every printed line's normal
// line feed, so consecutive rows sit a bit further apart without touching the
// printer's default line-spacing register.
const FOODCOURT_EXTRA_LINE_FEED = 1;

export function buildReceiptBytes(transaction, options = {}) {
    const { lines } = receiptLines(transaction, options);
    const extraLineFeed = transaction.template === 'FOODCOURT' ? FOODCOURT_EXTRA_LINE_FEED : 0;
    const bytes = [ESC, 0x40];
    // Register a real tab stop at the totals indent column: leading space characters
    // get silently stripped by many cheap ESC/POS clone printers, but a proper tab
    // command is reliably honored.
    bytes.push(ESC, 0x44, TOTALS_INDENT_WIDTH, 0x00);
    const encoder = new TextEncoder();
    const pushText = (text) => {
        for (const byte of encoder.encode(text)) {
            bytes.push(byte);
        }
    };

    for (const line of lines) {
        if (line.feed !== undefined) {
            // Print and feed paper n dots — a small margin, without the full height
            // (and left/right/bold state churn) of an empty text line.
            bytes.push(ESC, 0x4a, line.feed);
            continue;
        }

        if (line.size) {
            // GS ! n: low nibble = height multiplier - 1, high nibble = width multiplier - 1.
            const magnification = Math.max(1, line.size) - 1;
            bytes.push(GS, 0x21, (magnification << 4) | magnification);
        } else if (line.double) {
            bytes.push(GS, 0x21, 0x11);
        } else if (line.tall) {
            // Double height only (not width), so the label + right-aligned value still fit the paper width.
            bytes.push(GS, 0x21, 0x01);
        }

        bytes.push(ESC, 0x45, line.bold ? 1 : 0);
        bytes.push(ESC, 0x61, line.align === 'center' ? 1 : line.align === 'right' ? 2 : 0);

        if (line.indent) {
            bytes.push(0x09);
        }

        pushText(line.text);
        bytes.push(0x0a);

        if (extraLineFeed) {
            bytes.push(ESC, 0x4a, extraLineFeed);
        }

        bytes.push(ESC, 0x45, 0);
        bytes.push(ESC, 0x61, 0);

        if (line.size || line.double || line.tall) {
            bytes.push(GS, 0x21, 0x00);
        }
    }

    bytes.push(ESC, 0x64, 0x03);
    bytes.push(GS, 0x56, 0x42, 0x00);

    return new Uint8Array(bytes);
}
