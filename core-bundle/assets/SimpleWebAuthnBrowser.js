import { browserSupportsWebAuthn, startAuthentication, startRegistration } from '@simplewebauthn/browser';

window.SimpleWebAuthnBrowser = {
    startAuthentication: startAuthentication,
    startRegistration: startRegistration,
    browserSupportsWebAuthn: browserSupportsWebAuthn,
};
