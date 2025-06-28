import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['message'];

    static values = {
        unsupportedMessage: String,
        assertionFailureMessage: String,
        attestationFailureMessage: String,
        optionsFailureMessage: String,
        csrfUrl: String,
    };

    handleUnsupported() {
        this.messageTarget.innerHTML = this.renderMessage(this.unsupportedMessageValue);
    }

    handleAssertionFailure(e) {
        this.messageTarget.innerHTML = this.renderMessage(this.assertionFailureMessageValue);
    }

    handleAttestationFailure() {
        this.messageTarget.innerHTML = this.renderMessage(this.attestationFailureMessageValue);
    }

    handleOptionsFailure() {
        this.messageTarget.innerHTML = this.renderMessage(this.optionsFailureMessageValue);
    }

    loadCsrf() {
        if (this.csrfScript || !this.csrfUrlValue) {
            return;
        }

        // Make sure we always have the correct request token and cookie
        this.csrfScript = document.createElement('script');
        this.csrfScript.src = this.csrfUrlValue;
        this.csrfScript.async = true;
        document.body.append(this.csrfScript);
    }

    renderMessage(message, type) {
        return `<p class="tl_${type ?? 'error'}">${message}</p>`;
    }
}
