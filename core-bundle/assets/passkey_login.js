import { browserSupportsWebAuthn, startAuthentication } from '@simplewebauthn/browser';

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

        const resp = await fetch(config.optionsUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({}),
        });

        // Make sure we always have the correct request token and cookie
        const script = document.createElement('script');
        script.src = config.requestTokenScript;
        script.async = true;
        document.body.append(script);

        const optionsJSON = await resp.json();

        if ('error' === optionsJSON.status) {
            elemError.innerText = config.assertionFailure;

            return;
        }

        let attResp;

        try {
            attResp = await startAuthentication({ optionsJSON });
        } catch (error) {
            elemError.innerText = config.assertionFailure;

            throw error;
        }

        const verificationResp = await fetch(config.resultUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(attResp),
        });

        const verificationJSON = await verificationResp.json();

        if ('error' === verificationJSON.status) {
            elemError.innerText = config.assertionFailure;

            return;
        }

        window.location = config.redirect || window.location.href;
    });
};

const selector = '[data-passkey-login]';

new MutationObserver((mutationsList) => {
    for (const mutation of mutationsList) {
        if (mutation.type === 'childList') {
            for (const node of mutation.addedNodes) {
                if (node.matches?.(selector)) {
                    init(node);
                }

                if (node.querySelectorAll) {
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
