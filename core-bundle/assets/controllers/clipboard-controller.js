import { Controller } from '@hotwired/stimulus';
import { Message } from '../modules/message';

export default class extends Controller {
    static values = {
        content: String,
        message: {
            type: String,
            default: 'Copied to clipboard!',
        },
    };

    write() {
        navigator.clipboard.writeText(this.contentValue).then(() => Message.info(this.messageValue));
    }
}
