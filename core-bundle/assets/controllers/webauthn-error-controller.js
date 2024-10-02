import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['messages'];

    static values = {
        unsupportedMessage: String,
        assertionFailureMessage: String,
        attestationFailureMessage: String
    }

    handleUnsupported() {
        this.messagesTarget.innerHTML = this.renderMessage(this.unsupportedMessageValue);
    }

    handleAssertionFailure(e) {
        this.messagesTarget.innerHTML = this.renderMessage(this.assertionFailureMessageValue);
    }

    handleAttestationFailure() {
        this.messagesTarget.innerHTML = this.renderMessage(this.attestationFailureMessageValue);
    }

    renderMessage(message, type) {
        type = type ?? 'error';

        return `<p class="tl_${type}">${message}</p>`; 
    }
}
