import type { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

let lastTimestamp = -1;
let sequenceCounter = 0;

/**
 * Generates an RFC 9562 compliant UUIDv7 identifier.
 * UUIDv7 provides millisecond-precision time ordering and monotonicity,
 * ensuring client-generated IDs sort chronologically and align with
 * backend UUIDv7 database storage.
 */
export function generateUUID(): string {
    let timestamp = Date.now();

    if (timestamp <= lastTimestamp) {
        // Increment sequence counter within the same millisecond
        sequenceCounter = (sequenceCounter + 1) & 0xfff;
        if (sequenceCounter === 0) {
            // Sequence counter overflow within the same millisecond; increment timestamp
            timestamp = lastTimestamp + 1;
        } else {
            timestamp = lastTimestamp;
        }
    } else {
        sequenceCounter = 0;
    }
    lastTimestamp = timestamp;

    const bytes = new Uint8Array(16);
    if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
        crypto.getRandomValues(bytes);
    } else {
        for (let i = 0; i < 16; i++) {
            bytes[i] = (Math.random() * 256) | 0;
        }
    }

    // 48-bit unix timestamp in milliseconds (bytes 0 to 5)
    bytes[0] = (timestamp / 0x10000000000) & 0xff;
    bytes[1] = (timestamp / 0x100000000) & 0xff;
    bytes[2] = (timestamp / 0x1000000) & 0xff;
    bytes[3] = (timestamp / 0x10000) & 0xff;
    bytes[4] = (timestamp / 0x100) & 0xff;
    bytes[5] = timestamp & 0xff;

    // 4-bit version 7 + top 4 bits of 12-bit sequence counter (byte 6)
    bytes[6] = 0x70 | ((sequenceCounter >>> 8) & 0x0f);
    // Lower 8 bits of 12-bit sequence counter (byte 7)
    bytes[7] = sequenceCounter & 0xff;

    // 2-bit variant (10xx xxxx) for RFC 9562 / RFC 4122 (byte 8)
    bytes[8] = (bytes[8] & 0x3f) | 0x80;

    // bytes 9-15 remain cryptographically random

    let hex = '';
    for (let i = 0; i < 16; i++) {
        hex += bytes[i].toString(16).padStart(2, '0');
        if (i === 3 || i === 5 || i === 7 || i === 9) {
            hex += '-';
        }
    }

    return hex;
}
