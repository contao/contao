export class Message {
    /**
     * Display an info message.
     *
     * @param message Text or HTML that will be put into the message body.
     */
    static info(message) {
        Message._addMessage(message, 'info');
    }

    /**
     * Display an error message.
     *
     * @param message Text or HTML that will be put into the message body.
     */
    static error(message) {
        Message._addMessage(message, 'error');
    }

    static _addMessage(message, type) {
        const event = new CustomEvent('contao--message', {
            detail: {
                type,
                message,
            },
        });

        document.dispatchEvent(event);
    }
}
