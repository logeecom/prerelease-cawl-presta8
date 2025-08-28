import DOMPurify from 'dompurify';

/**
 * Recursively sanitizes all string values within any data structure to protect against XSS attacks.
 *
 * @param input
 * @returns {{}|*|string}
 */
const sanitize = (input) => {
    if (typeof input === 'string') {
        return DOMPurify.sanitize(input);
    }

    if (Array.isArray(input)) {
        return input.map(sanitize);
    }

    if (input !== null && typeof input === 'object') {
        const sanitized = {};
        for (const key in input) {
            if (input.hasOwnProperty(key)) {
                sanitized[key] = sanitize(input[key]);
            }
        }
        return sanitized;
    }

    return input;
}

OnlinePaymentsFE.sanitize = sanitize;