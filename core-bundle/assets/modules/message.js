export const Message = {
    /**
     * Display an info message.
     *
     * @param message Text or HTML that will be put into the message body.
     */
    info(message) {
        Message._addMessage(message, 'info');
    },

    /**
     * Display an error message.
     *
     * @param message Text or HTML that will be put into the message body.
     */
    error(message) {
        Message._addMessage(message, 'error');
    },

    _addMessage(message, type) {
        const event = new CustomEvent('contao--message', {
            detail: {
                type,
                message,
            },
        });

        document.dispatchEvent(event);
    },
};
