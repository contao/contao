import { Controller } from '@hotwired/stimulus';
import { Message } from '../modules/message';

export default class extends Controller {
    static values = {
        content: String,
        message: {
            type: String,
            default: 'Copied to clipboard.',
        },
    };

    async write() {
        if (!navigator.clipboard) {
            if (window.console) {
                console.error('The clipboard API is not available. Make sure you use a secure context (https).');
            }

            return;
        }

        await navigator.clipboard.writeText(this.contentValue);

        Message.info(this.messageValue);
    }
}
