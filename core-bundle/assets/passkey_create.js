import { browserSupportsWebAuthn, startRegistration } from '@simplewebauthn/browser';

const initialized = new WeakMap();

const init = (element) => {
    if (initialized.has(element)) {
        return;
    }

    initialized.set(element, true);

    const button = element.querySelector('[data-passkey-button]');
    const elemError = document.querySelector('[data-passkey-error]');

    if (!button || !elemError || !element.dataset.passkeyConfig) {
        return;
    }

    const config = JSON.parse(element.dataset.passkeyConfig);

    button.addEventListener('click', async () => {
        elemError.innerHTML = '';

        if (!browserSupportsWebAuthn()) {
            elemError.innerHTML = config.unsupported;

            return;
        }

        const resp = await fetch(config.requestUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({}),
        });

        const optionsJSON = await resp.json();

        if ('error' === optionsJSON.status) {
            elemError.innerText = config.attestationFailure;

            return;
        }

        let attResp;

        try {
            attResp = await startRegistration({ optionsJSON });
        } catch (error) {
            if (error.name === 'InvalidStateError') {
                elemError.innerText = config.invalidState;
            } else {
                elemError.innerText = error;
            }

            throw error;
        }

        const verificationResp = await fetch(config.responseUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(attResp),
        });

        const verificationJSON = await verificationResp.json();

        if ('error' === verificationJSON.status) {
            elemError.innerText = config.attestationFailure;

            return;
        }

        window.location = config.redirect || window.location.href;
    });

    // Set focus on name input, if available
    const edit = element.querySelector('input[name="passkey_name"]');

    if (edit) {
        edit.focus();
        edit.select();
    }
};

const selector = '[data-passkey-create]';

new MutationObserver((mutationsList) => {
    for (const mutation of mutationsList) {
        if (mutation.type === 'childList') {
            for (const node of mutation.addedNodes) {
                if (node.matches?.(selector)) {
                    init(node);
                }

                if (element.querySelectorAll) {
                    for (const element of node.querySelectorAll(selector)) {
                        init(element);
                    }
                }
            }
        }
    }
}).observe(document, {
    attributes: false,
    childList: true,
    subtree: true,
});

for (const element of document.querySelectorAll(selector)) {
    init(element);
}
