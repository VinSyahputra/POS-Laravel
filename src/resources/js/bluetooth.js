const PRINTER_SERVICE_UUIDS = [
    '000018f0-0000-1000-8000-00805f9b34fb',
    '0000ff00-0000-1000-8000-00805f9b34fb',
    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
];

let connectedDevice = null;
let connectedCharacteristic = null;

export function isBluetoothSupported() {
    return typeof navigator !== 'undefined' && 'bluetooth' in navigator;
}

export function getStoredPrinterName() {
    return localStorage.getItem('pos_printer_name') ?? '';
}

export function getLastDevice() {
    return connectedDevice;
}

export async function requestPrinter() {
    if (!isBluetoothSupported()) {
        throw new Error('Browser ini tidak mendukung Web Bluetooth API. Gunakan Chrome atau Edge (berbasis Chromium).');
    }

    const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: PRINTER_SERVICE_UUIDS,
    });

    localStorage.setItem('pos_printer_name', device.name ?? '');
    connectedDevice = device;
    connectedCharacteristic = null;

    return device;
}

export async function restorePairedPrinter() {
    if (!isBluetoothSupported() || typeof navigator.bluetooth.getDevices !== 'function') {
        return null;
    }

    try {
        const devices = await navigator.bluetooth.getDevices();
        const printer = devices[devices.length - 1] ?? null;

        if (printer) {
            connectedDevice = printer;
            connectedCharacteristic = null;
        }

        return printer;
    } catch {
        return null;
    }
}

async function findWritableCharacteristic(server) {
    const services = await server.getPrimaryServices();

    for (const service of services) {
        const characteristics = await service.getCharacteristics();
        const writable = characteristics.find(
            (characteristic) => characteristic.properties.write || characteristic.properties.writeWithoutResponse
        );

        if (writable) {
            return writable;
        }
    }

    return null;
}

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function writeChunked(characteristic, bytes) {
    const CHUNK_SIZE = 512;

    for (let offset = 0; offset < bytes.length; offset += CHUNK_SIZE) {
        const chunk = bytes.slice(offset, offset + CHUNK_SIZE);

        if (characteristic.properties.write) {
            await characteristic.writeValue(chunk);
        } else {
            await characteristic.writeValueWithoutResponse(chunk);
        }

        await delay(24);
    }
}

export async function connectPrinter() {
    if (!isBluetoothSupported()) {
        throw new Error('Browser ini tidak mendukung Web Bluetooth API. Gunakan Chrome atau Edge (berbasis Chromium).');
    }

    if (!connectedDevice) {
        throw new Error('Belum ada printer yang dipasangkan. Pasangkan printer terlebih dahulu.');
    }

    const server = await connectedDevice.gatt.connect();
    const characteristic = await findWritableCharacteristic(server);

    if (!characteristic) {
        throw new Error('Printer tidak memiliki characteristic cetak yang bisa ditulis.');
    }

    connectedCharacteristic = characteristic;

    return characteristic;
}

export async function writeBytes(bytes) {
    if (!connectedCharacteristic) {
        await connectPrinter();
    }

    await writeChunked(connectedCharacteristic, new Uint8Array(bytes));

    return true;
}

export function disconnectPrinter() {
    if (connectedDevice?.gatt?.connected) {
        connectedDevice.gatt.disconnect();
    }

    connectedCharacteristic = null;
}
