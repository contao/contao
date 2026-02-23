/**
 * Display an info message.
 *
 * @param message Text or HTML that will be put into the message body.
 */
export function info(message) {
    _addMessage(message, 'info');
}

/**
 * Display an error message.
 *
 * @param message Text or HTML that will be put into the message body.
 */
export function error(message) {
    _addMessage(message, 'error');
}

function _addMessage(message, type) {
    const event = new CustomEvent('contao--message', {
        detail: {
            type,
            message,
        },
    });

    document.dispatchEvent(event);
}
