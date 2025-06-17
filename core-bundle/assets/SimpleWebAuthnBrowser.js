import { startAuthentication, startRegistration, browserSupportsWebAuthn } from '@simplewebauthn/browser';

window.SimpleWebAuthnBrowser = {
    startAuthentication: startAuthentication,
    startRegistration: startRegistration,
    browserSupportsWebAuthn: browserSupportsWebAuthn,
};
