/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js":
/*!*************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   base64URLStringToBuffer: function() { return /* binding */ base64URLStringToBuffer; }
/* harmony export */ });
/**
 * Convert from a Base64URL-encoded string to an Array Buffer. Best used when converting a
 * credential ID from a JSON string to an ArrayBuffer, like in allowCredentials or
 * excludeCredentials
 *
 * Helper method to compliment `bufferToBase64URLString`
 */
function base64URLStringToBuffer(base64URLString) {
    // Convert from Base64URL to Base64
    const base64 = base64URLString.replace(/-/g, '+').replace(/_/g, '/');
    /**
     * Pad with '=' until it's a multiple of four
     * (4 - (85 % 4 = 1) = 3) % 4 = 3 padding
     * (4 - (86 % 4 = 2) = 2) % 4 = 2 padding
     * (4 - (87 % 4 = 3) = 1) % 4 = 1 padding
     * (4 - (88 % 4 = 0) = 4) % 4 = 0 padding
     */
    const padLength = (4 - (base64.length % 4)) % 4;
    const padded = base64.padEnd(base64.length + padLength, '=');
    // Convert to a binary string
    const binary = atob(padded);
    // Convert binary string to buffer
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return buffer;
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js":
/*!*************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _browserSupportsWebAuthnInternals: function() { return /* binding */ _browserSupportsWebAuthnInternals; },
/* harmony export */   browserSupportsWebAuthn: function() { return /* binding */ browserSupportsWebAuthn; }
/* harmony export */ });
/**
 * Determine if the browser is capable of Webauthn
 */
function browserSupportsWebAuthn() {
    return _browserSupportsWebAuthnInternals.stubThis(globalThis?.PublicKeyCredential !== undefined &&
        typeof globalThis.PublicKeyCredential === 'function');
}
/**
 * Make it possible to stub the return value during testing
 * @ignore Don't include this in docs output
 */
const _browserSupportsWebAuthnInternals = {
    stubThis: (value) => value,
};


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js":
/*!*********************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js ***!
  \*********************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   _browserSupportsWebAuthnAutofillInternals: function() { return /* binding */ _browserSupportsWebAuthnAutofillInternals; },
/* harmony export */   browserSupportsWebAuthnAutofill: function() { return /* binding */ browserSupportsWebAuthnAutofill; }
/* harmony export */ });
/* harmony import */ var _browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");

/**
 * Determine if the browser supports conditional UI, so that WebAuthn credentials can
 * be shown to the user in the browser's typical password autofill popup.
 */
function browserSupportsWebAuthnAutofill() {
    if (!(0,_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__.browserSupportsWebAuthn)()) {
        return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
    }
    /**
     * I don't like the `as unknown` here but there's a `declare var PublicKeyCredential` in
     * TS' DOM lib that's making it difficult for me to just go `as PublicKeyCredentialFuture` as I
     * want. I think I'm fine with this for now since it's _supposed_ to be temporary, until TS types
     * have a chance to catch up.
     */
    const globalPublicKeyCredential = globalThis
        .PublicKeyCredential;
    if (globalPublicKeyCredential?.isConditionalMediationAvailable === undefined) {
        return _browserSupportsWebAuthnAutofillInternals.stubThis(new Promise((resolve) => resolve(false)));
    }
    return _browserSupportsWebAuthnAutofillInternals.stubThis(globalPublicKeyCredential.isConditionalMediationAvailable());
}
// Make it possible to stub the return value during testing
const _browserSupportsWebAuthnAutofillInternals = {
    stubThis: (value) => value,
};


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js":
/*!*************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   bufferToBase64URLString: function() { return /* binding */ bufferToBase64URLString; }
/* harmony export */ });
/**
 * Convert the given array buffer into a Base64URL-encoded string. Ideal for converting various
 * credential response ArrayBuffers to string for sending back to the server as JSON.
 *
 * Helper method to compliment `base64URLStringToBuffer`
 */
function bufferToBase64URLString(buffer) {
    const bytes = new Uint8Array(buffer);
    let str = '';
    for (const charCode of bytes) {
        str += String.fromCharCode(charCode);
    }
    const base64String = btoa(str);
    return base64String.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js":
/*!*****************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js ***!
  \*****************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   identifyAuthenticationError: function() { return /* binding */ identifyAuthenticationError; }
/* harmony export */ });
/* harmony import */ var _isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isValidDomain.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js");
/* harmony import */ var _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./webAuthnError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js");


/**
 * Attempt to intuit _why_ an error was raised after calling `navigator.credentials.get()`
 */
function identifyAuthenticationError({ error, options, }) {
    const { publicKey } = options;
    if (!publicKey) {
        throw Error('options was missing required publicKey property');
    }
    if (error.name === 'AbortError') {
        if (options.signal instanceof AbortSignal) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 16)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'Authentication ceremony was sent an abort signal',
                code: 'ERROR_CEREMONY_ABORTED',
                cause: error,
            });
        }
    }
    else if (error.name === 'NotAllowedError') {
        /**
         * Pass the error directly through. Platforms are overloading this error beyond what the spec
         * defines and we don't want to overwrite potentially useful error messages.
         */
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: error.message,
            code: 'ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY',
            cause: error,
        });
    }
    else if (error.name === 'SecurityError') {
        const effectiveDomain = globalThis.location.hostname;
        if (!(0,_isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__.isValidDomain)(effectiveDomain)) {
            // https://www.w3.org/TR/webauthn-2/#sctn-discover-from-external-source (Step 5)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `${globalThis.location.hostname} is an invalid domain`,
                code: 'ERROR_INVALID_DOMAIN',
                cause: error,
            });
        }
        else if (publicKey.rpId !== effectiveDomain) {
            // https://www.w3.org/TR/webauthn-2/#sctn-discover-from-external-source (Step 6)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `The RP ID "${publicKey.rpId}" is invalid for this domain`,
                code: 'ERROR_INVALID_RP_ID',
                cause: error,
            });
        }
    }
    else if (error.name === 'UnknownError') {
        // https://www.w3.org/TR/webauthn-2/#sctn-op-get-assertion (Step 1)
        // https://www.w3.org/TR/webauthn-2/#sctn-op-get-assertion (Step 12)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'The authenticator was unable to process the specified options, or could not create a new assertion signature',
            code: 'ERROR_AUTHENTICATOR_GENERAL_ERROR',
            cause: error,
        });
    }
    return error;
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js":
/*!***************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   identifyRegistrationError: function() { return /* binding */ identifyRegistrationError; }
/* harmony export */ });
/* harmony import */ var _isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./isValidDomain.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js");
/* harmony import */ var _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./webAuthnError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js");


/**
 * Attempt to intuit _why_ an error was raised after calling `navigator.credentials.create()`
 */
function identifyRegistrationError({ error, options, }) {
    const { publicKey } = options;
    if (!publicKey) {
        throw Error('options was missing required publicKey property');
    }
    if (error.name === 'AbortError') {
        if (options.signal instanceof AbortSignal) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 16)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'Registration ceremony was sent an abort signal',
                code: 'ERROR_CEREMONY_ABORTED',
                cause: error,
            });
        }
    }
    else if (error.name === 'ConstraintError') {
        if (publicKey.authenticatorSelection?.requireResidentKey === true) {
            // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 4)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'Discoverable credentials were required but no available authenticator supported it',
                code: 'ERROR_AUTHENTICATOR_MISSING_DISCOVERABLE_CREDENTIAL_SUPPORT',
                cause: error,
            });
        }
        else if (
        // @ts-ignore: `mediation` doesn't yet exist on CredentialCreationOptions but it's possible as of Sept 2024
        options.mediation === 'conditional' &&
            publicKey.authenticatorSelection?.userVerification === 'required') {
            // https://w3c.github.io/webauthn/#sctn-createCredential (Step 22.4)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'User verification was required during automatic registration but it could not be performed',
                code: 'ERROR_AUTO_REGISTER_USER_VERIFICATION_FAILURE',
                cause: error,
            });
        }
        else if (publicKey.authenticatorSelection?.userVerification === 'required') {
            // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 5)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'User verification was required but no available authenticator supported it',
                code: 'ERROR_AUTHENTICATOR_MISSING_USER_VERIFICATION_SUPPORT',
                cause: error,
            });
        }
    }
    else if (error.name === 'InvalidStateError') {
        // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 20)
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 3)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'The authenticator was previously registered',
            code: 'ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED',
            cause: error,
        });
    }
    else if (error.name === 'NotAllowedError') {
        /**
         * Pass the error directly through. Platforms are overloading this error beyond what the spec
         * defines and we don't want to overwrite potentially useful error messages.
         */
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: error.message,
            code: 'ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY',
            cause: error,
        });
    }
    else if (error.name === 'NotSupportedError') {
        const validPubKeyCredParams = publicKey.pubKeyCredParams.filter((param) => param.type === 'public-key');
        if (validPubKeyCredParams.length === 0) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 10)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'No entry in pubKeyCredParams was of type "public-key"',
                code: 'ERROR_MALFORMED_PUBKEYCREDPARAMS',
                cause: error,
            });
        }
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 2)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'No available authenticator supported any of the specified pubKeyCredParams algorithms',
            code: 'ERROR_AUTHENTICATOR_NO_SUPPORTED_PUBKEYCREDPARAMS_ALG',
            cause: error,
        });
    }
    else if (error.name === 'SecurityError') {
        const effectiveDomain = globalThis.location.hostname;
        if (!(0,_isValidDomain_js__WEBPACK_IMPORTED_MODULE_0__.isValidDomain)(effectiveDomain)) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 7)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `${globalThis.location.hostname} is an invalid domain`,
                code: 'ERROR_INVALID_DOMAIN',
                cause: error,
            });
        }
        else if (publicKey.rp.id !== effectiveDomain) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 8)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: `The RP ID "${publicKey.rp.id}" is invalid for this domain`,
                code: 'ERROR_INVALID_RP_ID',
                cause: error,
            });
        }
    }
    else if (error.name === 'TypeError') {
        if (publicKey.user.id.byteLength < 1 || publicKey.user.id.byteLength > 64) {
            // https://www.w3.org/TR/webauthn-2/#sctn-createCredential (Step 5)
            return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
                message: 'User ID was not between 1 and 64 characters',
                code: 'ERROR_INVALID_USER_ID_LENGTH',
                cause: error,
            });
        }
    }
    else if (error.name === 'UnknownError') {
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 1)
        // https://www.w3.org/TR/webauthn-2/#sctn-op-make-cred (Step 8)
        return new _webAuthnError_js__WEBPACK_IMPORTED_MODULE_1__.WebAuthnError({
            message: 'The authenticator was unable to process the specified options, or could not create a new credential',
            code: 'ERROR_AUTHENTICATOR_GENERAL_ERROR',
            cause: error,
        });
    }
    return error;
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/isValidDomain.js ***!
  \***************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isValidDomain: function() { return /* binding */ isValidDomain; }
/* harmony export */ });
/**
 * A simple test to determine if a hostname is a properly-formatted domain name
 *
 * A "valid domain" is defined here: https://url.spec.whatwg.org/#valid-domain
 *
 * Regex sourced from here:
 * https://www.oreilly.com/library/view/regular-expressions-cookbook/9781449327453/ch08s15.html
 */
function isValidDomain(hostname) {
    return (
    // Consider localhost valid as well since it's okay wrt Secure Contexts
    hostname === 'localhost' ||
        /^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i.test(hostname));
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/platformAuthenticatorIsAvailable.js":
/*!**********************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/platformAuthenticatorIsAvailable.js ***!
  \**********************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   platformAuthenticatorIsAvailable: function() { return /* binding */ platformAuthenticatorIsAvailable; }
/* harmony export */ });
/* harmony import */ var _browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");

/**
 * Determine whether the browser can communicate with a built-in authenticator, like
 * Touch ID, Android fingerprint scanner, or Windows Hello.
 *
 * This method will _not_ be able to tell you the name of the platform authenticator.
 */
function platformAuthenticatorIsAvailable() {
    if (!(0,_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_0__.browserSupportsWebAuthn)()) {
        return new Promise((resolve) => resolve(false));
    }
    return PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js":
/*!***************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   toAuthenticatorAttachment: function() { return /* binding */ toAuthenticatorAttachment; }
/* harmony export */ });
const attachments = ['cross-platform', 'platform'];
/**
 * If possible coerce a `string` value into a known `AuthenticatorAttachment`
 */
function toAuthenticatorAttachment(attachment) {
    if (!attachment) {
        return;
    }
    if (attachments.indexOf(attachment) < 0) {
        return;
    }
    return attachment;
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js":
/*!*********************************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js ***!
  \*********************************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   toPublicKeyCredentialDescriptor: function() { return /* binding */ toPublicKeyCredentialDescriptor; }
/* harmony export */ });
/* harmony import */ var _base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");

function toPublicKeyCredentialDescriptor(descriptor) {
    const { id } = descriptor;
    return {
        ...descriptor,
        id: (0,_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_0__.base64URLStringToBuffer)(id),
        /**
         * `descriptor.transports` is an array of our `AuthenticatorTransportFuture` that includes newer
         * transports that TypeScript's DOM lib is ignorant of. Convince TS that our list of transports
         * are fine to pass to WebAuthn since browsers will recognize the new value.
         */
        transports: descriptor.transports,
    };
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js":
/*!**********************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js ***!
  \**********************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WebAuthnAbortService: function() { return /* binding */ WebAuthnAbortService; }
/* harmony export */ });
class BaseWebAuthnAbortService {
    constructor() {
        Object.defineProperty(this, "controller", {
            enumerable: true,
            configurable: true,
            writable: true,
            value: void 0
        });
    }
    createNewAbortSignal() {
        // Abort any existing calls to navigator.credentials.create() or navigator.credentials.get()
        if (this.controller) {
            const abortError = new Error('Cancelling existing WebAuthn API call for new one');
            abortError.name = 'AbortError';
            this.controller.abort(abortError);
        }
        const newController = new AbortController();
        this.controller = newController;
        return newController.signal;
    }
    cancelCeremony() {
        if (this.controller) {
            const abortError = new Error('Manually cancelling existing WebAuthn API call');
            abortError.name = 'AbortError';
            this.controller.abort(abortError);
            this.controller = undefined;
        }
    }
}
/**
 * A service singleton to help ensure that only a single WebAuthn ceremony is active at a time.
 *
 * Users of **@simplewebauthn/browser** shouldn't typically need to use this, but it can help e.g.
 * developers building projects that use client-side routing to better control the behavior of
 * their UX in response to router navigation events.
 */
const WebAuthnAbortService = new BaseWebAuthnAbortService();


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js":
/*!***************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js ***!
  \***************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WebAuthnError: function() { return /* binding */ WebAuthnError; }
/* harmony export */ });
/**
 * A custom Error used to return a more nuanced error detailing _why_ one of the eight documented
 * errors in the spec was raised after calling `navigator.credentials.create()` or
 * `navigator.credentials.get()`:
 *
 * - `AbortError`
 * - `ConstraintError`
 * - `InvalidStateError`
 * - `NotAllowedError`
 * - `NotSupportedError`
 * - `SecurityError`
 * - `TypeError`
 * - `UnknownError`
 *
 * Error messages were determined through investigation of the spec to determine under which
 * scenarios a given error would be raised.
 */
class WebAuthnError extends Error {
    constructor({ message, code, cause, name, }) {
        // @ts-ignore: help Rollup understand that `cause` is okay to set
        super(message, { cause });
        Object.defineProperty(this, "code", {
            enumerable: true,
            configurable: true,
            writable: true,
            value: void 0
        });
        this.name = name ?? cause.name;
        this.code = code;
    }
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/index.js":
/*!***********************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/index.js ***!
  \***********************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WebAuthnAbortService: function() { return /* reexport safe */ _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_7__.WebAuthnAbortService; },
/* harmony export */   WebAuthnError: function() { return /* reexport safe */ _helpers_webAuthnError_js__WEBPACK_IMPORTED_MODULE_8__.WebAuthnError; },
/* harmony export */   _browserSupportsWebAuthnAutofillInternals: function() { return /* reexport safe */ _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_4__._browserSupportsWebAuthnAutofillInternals; },
/* harmony export */   _browserSupportsWebAuthnInternals: function() { return /* reexport safe */ _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__._browserSupportsWebAuthnInternals; },
/* harmony export */   base64URLStringToBuffer: function() { return /* reexport safe */ _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_5__.base64URLStringToBuffer; },
/* harmony export */   browserSupportsWebAuthn: function() { return /* reexport safe */ _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__.browserSupportsWebAuthn; },
/* harmony export */   browserSupportsWebAuthnAutofill: function() { return /* reexport safe */ _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_4__.browserSupportsWebAuthnAutofill; },
/* harmony export */   bufferToBase64URLString: function() { return /* reexport safe */ _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_6__.bufferToBase64URLString; },
/* harmony export */   platformAuthenticatorIsAvailable: function() { return /* reexport safe */ _helpers_platformAuthenticatorIsAvailable_js__WEBPACK_IMPORTED_MODULE_3__.platformAuthenticatorIsAvailable; },
/* harmony export */   startAuthentication: function() { return /* reexport safe */ _methods_startAuthentication_js__WEBPACK_IMPORTED_MODULE_1__.startAuthentication; },
/* harmony export */   startRegistration: function() { return /* reexport safe */ _methods_startRegistration_js__WEBPACK_IMPORTED_MODULE_0__.startRegistration; }
/* harmony export */ });
/* harmony import */ var _methods_startRegistration_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./methods/startRegistration.js */ "./node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js");
/* harmony import */ var _methods_startAuthentication_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./methods/startAuthentication.js */ "./node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js");
/* harmony import */ var _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./helpers/browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");
/* harmony import */ var _helpers_platformAuthenticatorIsAvailable_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./helpers/platformAuthenticatorIsAvailable.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/platformAuthenticatorIsAvailable.js");
/* harmony import */ var _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./helpers/browserSupportsWebAuthnAutofill.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js");
/* harmony import */ var _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./helpers/base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");
/* harmony import */ var _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./helpers/bufferToBase64URLString.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js");
/* harmony import */ var _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./helpers/webAuthnAbortService.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js");
/* harmony import */ var _helpers_webAuthnError_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./helpers/webAuthnError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnError.js");
/* harmony import */ var _types_index_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./types/index.js */ "./node_modules/@simplewebauthn/browser/esm/types/index.js");












/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js":
/*!*********************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/methods/startAuthentication.js ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   startAuthentication: function() { return /* binding */ startAuthentication; }
/* harmony export */ });
/* harmony import */ var _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../helpers/bufferToBase64URLString.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js");
/* harmony import */ var _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../helpers/base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");
/* harmony import */ var _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../helpers/browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");
/* harmony import */ var _helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../helpers/browserSupportsWebAuthnAutofill.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthnAutofill.js");
/* harmony import */ var _helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/toPublicKeyCredentialDescriptor.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js");
/* harmony import */ var _helpers_identifyAuthenticationError_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/identifyAuthenticationError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyAuthenticationError.js");
/* harmony import */ var _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../helpers/webAuthnAbortService.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js");
/* harmony import */ var _helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../helpers/toAuthenticatorAttachment.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js");








/**
 * Begin authenticator "login" via WebAuthn assertion
 *
 * @param optionsJSON Output from **@simplewebauthn/server**'s `generateAuthenticationOptions()`
 * @param useBrowserAutofill (Optional) Initialize conditional UI to enable logging in via browser autofill prompts. Defaults to `false`.
 * @param verifyBrowserAutofillInput (Optional) Ensure a suitable `<input>` element is present when `useBrowserAutofill` is `true`. Defaults to `true`.
 */
async function startAuthentication(options) {
    // @ts-ignore: Intentionally check for old call structure to warn about improper API call
    if (!options.optionsJSON && options.challenge) {
        console.warn('startAuthentication() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.');
        // @ts-ignore: Reassign the options, passed in as a positional argument, to the expected variable
        options = { optionsJSON: options };
    }
    const { optionsJSON, useBrowserAutofill = false, verifyBrowserAutofillInput = true, } = options;
    if (!(0,_helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__.browserSupportsWebAuthn)()) {
        throw new Error('WebAuthn is not supported in this browser');
    }
    // We need to avoid passing empty array to avoid blocking retrieval
    // of public key
    let allowCredentials;
    if (optionsJSON.allowCredentials?.length !== 0) {
        allowCredentials = optionsJSON.allowCredentials?.map(_helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_4__.toPublicKeyCredentialDescriptor);
    }
    // We need to convert some values to Uint8Arrays before passing the credentials to the navigator
    const publicKey = {
        ...optionsJSON,
        challenge: (0,_helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__.base64URLStringToBuffer)(optionsJSON.challenge),
        allowCredentials,
    };
    // Prepare options for `.get()`
    const getOptions = {};
    /**
     * Set up the page to prompt the user to select a credential for authentication via the browser's
     * input autofill mechanism.
     */
    if (useBrowserAutofill) {
        if (!(await (0,_helpers_browserSupportsWebAuthnAutofill_js__WEBPACK_IMPORTED_MODULE_3__.browserSupportsWebAuthnAutofill)())) {
            throw Error('Browser does not support WebAuthn autofill');
        }
        // Check for an <input> with "webauthn" in its `autocomplete` attribute
        const eligibleInputs = document.querySelectorAll("input[autocomplete$='webauthn']");
        // WebAuthn autofill requires at least one valid input
        if (eligibleInputs.length < 1 && verifyBrowserAutofillInput) {
            throw Error('No <input> with "webauthn" as the only or last value in its `autocomplete` attribute was detected');
        }
        // `CredentialMediationRequirement` doesn't know about "conditional" yet as of
        // typescript@4.6.3
        getOptions.mediation = 'conditional';
        // Conditional UI requires an empty allow list
        publicKey.allowCredentials = [];
    }
    // Finalize options
    getOptions.publicKey = publicKey;
    // Set up the ability to cancel this request if the user attempts another
    getOptions.signal = _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_6__.WebAuthnAbortService.createNewAbortSignal();
    // Wait for the user to complete assertion
    let credential;
    try {
        credential = (await navigator.credentials.get(getOptions));
    }
    catch (err) {
        throw (0,_helpers_identifyAuthenticationError_js__WEBPACK_IMPORTED_MODULE_5__.identifyAuthenticationError)({ error: err, options: getOptions });
    }
    if (!credential) {
        throw new Error('Authentication was not completed');
    }
    const { id, rawId, response, type } = credential;
    let userHandle = undefined;
    if (response.userHandle) {
        userHandle = (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.userHandle);
    }
    // Convert values to base64 to make it easier to send back to the server
    return {
        id,
        rawId: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(rawId),
        response: {
            authenticatorData: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.authenticatorData),
            clientDataJSON: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.clientDataJSON),
            signature: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.signature),
            userHandle,
        },
        type,
        clientExtensionResults: credential.getClientExtensionResults(),
        authenticatorAttachment: (0,_helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_7__.toAuthenticatorAttachment)(credential.authenticatorAttachment),
    };
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js":
/*!*******************************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/methods/startRegistration.js ***!
  \*******************************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   startRegistration: function() { return /* binding */ startRegistration; }
/* harmony export */ });
/* harmony import */ var _helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../helpers/bufferToBase64URLString.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/bufferToBase64URLString.js");
/* harmony import */ var _helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../helpers/base64URLStringToBuffer.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/base64URLStringToBuffer.js");
/* harmony import */ var _helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../helpers/browserSupportsWebAuthn.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/browserSupportsWebAuthn.js");
/* harmony import */ var _helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../helpers/toPublicKeyCredentialDescriptor.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toPublicKeyCredentialDescriptor.js");
/* harmony import */ var _helpers_identifyRegistrationError_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/identifyRegistrationError.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/identifyRegistrationError.js");
/* harmony import */ var _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../helpers/webAuthnAbortService.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/webAuthnAbortService.js");
/* harmony import */ var _helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../helpers/toAuthenticatorAttachment.js */ "./node_modules/@simplewebauthn/browser/esm/helpers/toAuthenticatorAttachment.js");







/**
 * Begin authenticator "registration" via WebAuthn attestation
 *
 * @param optionsJSON Output from **@simplewebauthn/server**'s `generateRegistrationOptions()`
 * @param useAutoRegister (Optional) Try to silently create a passkey with the password manager that the user just signed in with. Defaults to `false`.
 */
async function startRegistration(options) {
    // @ts-ignore: Intentionally check for old call structure to warn about improper API call
    if (!options.optionsJSON && options.challenge) {
        console.warn('startRegistration() was not called correctly. It will try to continue with the provided options, but this call should be refactored to use the expected call structure instead. See https://simplewebauthn.dev/docs/packages/browser#typeerror-cannot-read-properties-of-undefined-reading-challenge for more information.');
        // @ts-ignore: Reassign the options, passed in as a positional argument, to the expected variable
        options = { optionsJSON: options };
    }
    const { optionsJSON, useAutoRegister = false } = options;
    if (!(0,_helpers_browserSupportsWebAuthn_js__WEBPACK_IMPORTED_MODULE_2__.browserSupportsWebAuthn)()) {
        throw new Error('WebAuthn is not supported in this browser');
    }
    // We need to convert some values to Uint8Arrays before passing the credentials to the navigator
    const publicKey = {
        ...optionsJSON,
        challenge: (0,_helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__.base64URLStringToBuffer)(optionsJSON.challenge),
        user: {
            ...optionsJSON.user,
            id: (0,_helpers_base64URLStringToBuffer_js__WEBPACK_IMPORTED_MODULE_1__.base64URLStringToBuffer)(optionsJSON.user.id),
        },
        excludeCredentials: optionsJSON.excludeCredentials?.map(_helpers_toPublicKeyCredentialDescriptor_js__WEBPACK_IMPORTED_MODULE_3__.toPublicKeyCredentialDescriptor),
    };
    // Prepare options for `.create()`
    const createOptions = {};
    /**
     * Try to use conditional create to register a passkey for the user with the password manager
     * the user just used to authenticate with. The user won't be shown any prominent UI by the
     * browser.
     */
    if (useAutoRegister) {
        // @ts-ignore: `mediation` doesn't yet exist on CredentialCreationOptions but it's possible as of Sept 2024
        createOptions.mediation = 'conditional';
    }
    // Finalize options
    createOptions.publicKey = publicKey;
    // Set up the ability to cancel this request if the user attempts another
    createOptions.signal = _helpers_webAuthnAbortService_js__WEBPACK_IMPORTED_MODULE_5__.WebAuthnAbortService.createNewAbortSignal();
    // Wait for the user to complete attestation
    let credential;
    try {
        credential = (await navigator.credentials.create(createOptions));
    }
    catch (err) {
        throw (0,_helpers_identifyRegistrationError_js__WEBPACK_IMPORTED_MODULE_4__.identifyRegistrationError)({ error: err, options: createOptions });
    }
    if (!credential) {
        throw new Error('Registration was not completed');
    }
    const { id, rawId, response, type } = credential;
    // Continue to play it safe with `getTransports()` for now, even when L3 types say it's required
    let transports = undefined;
    if (typeof response.getTransports === 'function') {
        transports = response.getTransports();
    }
    // L3 says this is required, but browser and webview support are still not guaranteed.
    let responsePublicKeyAlgorithm = undefined;
    if (typeof response.getPublicKeyAlgorithm === 'function') {
        try {
            responsePublicKeyAlgorithm = response.getPublicKeyAlgorithm();
        }
        catch (error) {
            warnOnBrokenImplementation('getPublicKeyAlgorithm()', error);
        }
    }
    let responsePublicKey = undefined;
    if (typeof response.getPublicKey === 'function') {
        try {
            const _publicKey = response.getPublicKey();
            if (_publicKey !== null) {
                responsePublicKey = (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(_publicKey);
            }
        }
        catch (error) {
            warnOnBrokenImplementation('getPublicKey()', error);
        }
    }
    // L3 says this is required, but browser and webview support are still not guaranteed.
    let responseAuthenticatorData;
    if (typeof response.getAuthenticatorData === 'function') {
        try {
            responseAuthenticatorData = (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.getAuthenticatorData());
        }
        catch (error) {
            warnOnBrokenImplementation('getAuthenticatorData()', error);
        }
    }
    return {
        id,
        rawId: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(rawId),
        response: {
            attestationObject: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.attestationObject),
            clientDataJSON: (0,_helpers_bufferToBase64URLString_js__WEBPACK_IMPORTED_MODULE_0__.bufferToBase64URLString)(response.clientDataJSON),
            transports,
            publicKeyAlgorithm: responsePublicKeyAlgorithm,
            publicKey: responsePublicKey,
            authenticatorData: responseAuthenticatorData,
        },
        type,
        clientExtensionResults: credential.getClientExtensionResults(),
        authenticatorAttachment: (0,_helpers_toAuthenticatorAttachment_js__WEBPACK_IMPORTED_MODULE_6__.toAuthenticatorAttachment)(credential.authenticatorAttachment),
    };
}
/**
 * Visibly warn when we detect an issue related to a passkey provider intercepting WebAuthn API
 * calls
 */
function warnOnBrokenImplementation(methodName, cause) {
    console.warn(`The browser extension that intercepted this WebAuthn API call incorrectly implemented ${methodName}. You should report this error to them.\n`, cause);
}


/***/ }),

/***/ "./node_modules/@simplewebauthn/browser/esm/types/index.js":
/*!*****************************************************************!*\
  !*** ./node_modules/@simplewebauthn/browser/esm/types/index.js ***!
  \*****************************************************************/
/***/ (function(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);



/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
!function() {
/*!*********************************************!*\
  !*** ./core-bundle/assets/passkey_login.js ***!
  \*********************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _simplewebauthn_browser__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @simplewebauthn/browser */ "./node_modules/@simplewebauthn/browser/esm/index.js");

const initialized = new WeakMap();
const init = element => {
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
    if (!(0,_simplewebauthn_browser__WEBPACK_IMPORTED_MODULE_0__.browserSupportsWebAuthn)()) {
      elemError.innerHTML = config.unsupported;
      return;
    }
    const resp = await fetch(config.optionsUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({})
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
      attResp = await (0,_simplewebauthn_browser__WEBPACK_IMPORTED_MODULE_0__.startAuthentication)({
        optionsJSON
      });
    } catch (error) {
      elemError.innerText = config.assertionFailure;
      throw error;
    }
    const verificationResp = await fetch(config.resultUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(attResp)
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
new MutationObserver(mutationsList => {
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
  subtree: true
});
for (const element of document.querySelectorAll(selector)) {
  init(element);
}
}();
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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RUFBRTtFQUN2RDNDLElBQUksQ0FBQ0MsT0FBTyxDQUFDO0FBQ2pCLEMiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy9icm93c2VyU3VwcG9ydHNXZWJBdXRobkF1dG9maWxsLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy9idWZmZXJUb0Jhc2U2NFVSTFN0cmluZy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvaWRlbnRpZnlBdXRoZW50aWNhdGlvbkVycm9yLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy9pZGVudGlmeVJlZ2lzdHJhdGlvbkVycm9yLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy9pc1ZhbGlkRG9tYWluLmpzIiwid2VicGFjazovLy8uL25vZGVfbW9kdWxlcy9Ac2ltcGxld2ViYXV0aG4vYnJvd3Nlci9lc20vaGVscGVycy9wbGF0Zm9ybUF1dGhlbnRpY2F0b3JJc0F2YWlsYWJsZS5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvdG9QdWJsaWNLZXlDcmVkZW50aWFsRGVzY3JpcHRvci5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL2hlbHBlcnMvd2ViQXV0aG5BYm9ydFNlcnZpY2UuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9oZWxwZXJzL3dlYkF1dGhuRXJyb3IuanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS9pbmRleC5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL21ldGhvZHMvc3RhcnRBdXRoZW50aWNhdGlvbi5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQHNpbXBsZXdlYmF1dGhuL2Jyb3dzZXIvZXNtL21ldGhvZHMvc3RhcnRSZWdpc3RyYXRpb24uanMiLCJ3ZWJwYWNrOi8vLy4vbm9kZV9tb2R1bGVzL0BzaW1wbGV3ZWJhdXRobi9icm93c2VyL2VzbS90eXBlcy9pbmRleC5qcyIsIndlYnBhY2s6Ly8vd2VicGFjay9ib290c3RyYXAiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svcnVudGltZS9kZWZpbmUgcHJvcGVydHkgZ2V0dGVycyIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2hhc093blByb3BlcnR5IHNob3J0aGFuZCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvcGFzc2tleV9sb2dpbi5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyIvKipcbiAqIENvbnZlcnQgZnJvbSBhIEJhc2U2NFVSTC1lbmNvZGVkIHN0cmluZyB0byBhbiBBcnJheSBCdWZmZXIuIEJlc3QgdXNlZCB3aGVuIGNvbnZlcnRpbmcgYVxuICogY3JlZGVudGlhbCBJRCBmcm9tIGEgSlNPTiBzdHJpbmcgdG8gYW4gQXJyYXlCdWZmZXIsIGxpa2UgaW4gYWxsb3dDcmVkZW50aWFscyBvclxuICogZXhjbHVkZUNyZWRlbnRpYWxzXG4gKlxuICogSGVscGVyIG1ldGhvZCB0byBjb21wbGltZW50IGBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZ2BcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyKGJhc2U2NFVSTFN0cmluZykge1xuICAgIC8vIENvbnZlcnQgZnJvbSBCYXNlNjRVUkwgdG8gQmFzZTY0XG4gICAgY29uc3QgYmFzZTY0ID0gYmFzZTY0VVJMU3RyaW5nLnJlcGxhY2UoLy0vZywgJysnKS5yZXBsYWNlKC9fL2csICcvJyk7XG4gICAgLyoqXG4gICAgICogUGFkIHdpdGggJz0nIHVudGlsIGl0J3MgYSBtdWx0aXBsZSBvZiBmb3VyXG4gICAgICogKDQgLSAoODUgJSA0ID0gMSkgPSAzKSAlIDQgPSAzIHBhZGRpbmdcbiAgICAgKiAoNCAtICg4NiAlIDQgPSAyKSA9IDIpICUgNCA9IDIgcGFkZGluZ1xuICAgICAqICg0IC0gKDg3ICUgNCA9IDMpID0gMSkgJSA0ID0gMSBwYWRkaW5nXG4gICAgICogKDQgLSAoODggJSA0ID0gMCkgPSA0KSAlIDQgPSAwIHBhZGRpbmdcbiAgICAgKi9cbiAgICBjb25zdCBwYWRMZW5ndGggPSAoNCAtIChiYXNlNjQubGVuZ3RoICUgNCkpICUgNDtcbiAgICBjb25zdCBwYWRkZWQgPSBiYXNlNjQucGFkRW5kKGJhc2U2NC5sZW5ndGggKyBwYWRMZW5ndGgsICc9Jyk7XG4gICAgLy8gQ29udmVydCB0byBhIGJpbmFyeSBzdHJpbmdcbiAgICBjb25zdCBiaW5hcnkgPSBhdG9iKHBhZGRlZCk7XG4gICAgLy8gQ29udmVydCBiaW5hcnkgc3RyaW5nIHRvIGJ1ZmZlclxuICAgIGNvbnN0IGJ1ZmZlciA9IG5ldyBBcnJheUJ1ZmZlcihiaW5hcnkubGVuZ3RoKTtcbiAgICBjb25zdCBieXRlcyA9IG5ldyBVaW50OEFycmF5KGJ1ZmZlcik7XG4gICAgZm9yIChsZXQgaSA9IDA7IGkgPCBiaW5hcnkubGVuZ3RoOyBpKyspIHtcbiAgICAgICAgYnl0ZXNbaV0gPSBiaW5hcnkuY2hhckNvZGVBdChpKTtcbiAgICB9XG4gICAgcmV0dXJuIGJ1ZmZlcjtcbn1cbiIsIi8qKlxuICogRGV0ZXJtaW5lIGlmIHRoZSBicm93c2VyIGlzIGNhcGFibGUgb2YgV2ViYXV0aG5cbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuKCkge1xuICAgIHJldHVybiBfYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5JbnRlcm5hbHMuc3R1YlRoaXMoZ2xvYmFsVGhpcz8uUHVibGljS2V5Q3JlZGVudGlhbCAhPT0gdW5kZWZpbmVkICYmXG4gICAgICAgIHR5cGVvZiBnbG9iYWxUaGlzLlB1YmxpY0tleUNyZWRlbnRpYWwgPT09ICdmdW5jdGlvbicpO1xufVxuLyoqXG4gKiBNYWtlIGl0IHBvc3NpYmxlIHRvIHN0dWIgdGhlIHJldHVybiB2YWx1ZSBkdXJpbmcgdGVzdGluZ1xuICogQGlnbm9yZSBEb24ndCBpbmNsdWRlIHRoaXMgaW4gZG9jcyBvdXRwdXRcbiAqL1xuZXhwb3J0IGNvbnN0IF9icm93c2VyU3VwcG9ydHNXZWJBdXRobkludGVybmFscyA9IHtcbiAgICBzdHViVGhpczogKHZhbHVlKSA9PiB2YWx1ZSxcbn07XG4iLCJpbXBvcnQgeyBicm93c2VyU3VwcG9ydHNXZWJBdXRobiB9IGZyb20gJy4vYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4uanMnO1xuLyoqXG4gKiBEZXRlcm1pbmUgaWYgdGhlIGJyb3dzZXIgc3VwcG9ydHMgY29uZGl0aW9uYWwgVUksIHNvIHRoYXQgV2ViQXV0aG4gY3JlZGVudGlhbHMgY2FuXG4gKiBiZSBzaG93biB0byB0aGUgdXNlciBpbiB0aGUgYnJvd3NlcidzIHR5cGljYWwgcGFzc3dvcmQgYXV0b2ZpbGwgcG9wdXAuXG4gKi9cbmV4cG9ydCBmdW5jdGlvbiBicm93c2VyU3VwcG9ydHNXZWJBdXRobkF1dG9maWxsKCkge1xuICAgIGlmICghYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4oKSkge1xuICAgICAgICByZXR1cm4gX2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuQXV0b2ZpbGxJbnRlcm5hbHMuc3R1YlRoaXMobmV3IFByb21pc2UoKHJlc29sdmUpID0+IHJlc29sdmUoZmFsc2UpKSk7XG4gICAgfVxuICAgIC8qKlxuICAgICAqIEkgZG9uJ3QgbGlrZSB0aGUgYGFzIHVua25vd25gIGhlcmUgYnV0IHRoZXJlJ3MgYSBgZGVjbGFyZSB2YXIgUHVibGljS2V5Q3JlZGVudGlhbGAgaW5cbiAgICAgKiBUUycgRE9NIGxpYiB0aGF0J3MgbWFraW5nIGl0IGRpZmZpY3VsdCBmb3IgbWUgdG8ganVzdCBnbyBgYXMgUHVibGljS2V5Q3JlZGVudGlhbEZ1dHVyZWAgYXMgSVxuICAgICAqIHdhbnQuIEkgdGhpbmsgSSdtIGZpbmUgd2l0aCB0aGlzIGZvciBub3cgc2luY2UgaXQncyBfc3VwcG9zZWRfIHRvIGJlIHRlbXBvcmFyeSwgdW50aWwgVFMgdHlwZXNcbiAgICAgKiBoYXZlIGEgY2hhbmNlIHRvIGNhdGNoIHVwLlxuICAgICAqL1xuICAgIGNvbnN0IGdsb2JhbFB1YmxpY0tleUNyZWRlbnRpYWwgPSBnbG9iYWxUaGlzXG4gICAgICAgIC5QdWJsaWNLZXlDcmVkZW50aWFsO1xuICAgIGlmIChnbG9iYWxQdWJsaWNLZXlDcmVkZW50aWFsPy5pc0NvbmRpdGlvbmFsTWVkaWF0aW9uQXZhaWxhYmxlID09PSB1bmRlZmluZWQpIHtcbiAgICAgICAgcmV0dXJuIF9icm93c2VyU3VwcG9ydHNXZWJBdXRobkF1dG9maWxsSW50ZXJuYWxzLnN0dWJUaGlzKG5ldyBQcm9taXNlKChyZXNvbHZlKSA9PiByZXNvbHZlKGZhbHNlKSkpO1xuICAgIH1cbiAgICByZXR1cm4gX2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuQXV0b2ZpbGxJbnRlcm5hbHMuc3R1YlRoaXMoZ2xvYmFsUHVibGljS2V5Q3JlZGVudGlhbC5pc0NvbmRpdGlvbmFsTWVkaWF0aW9uQXZhaWxhYmxlKCkpO1xufVxuLy8gTWFrZSBpdCBwb3NzaWJsZSB0byBzdHViIHRoZSByZXR1cm4gdmFsdWUgZHVyaW5nIHRlc3RpbmdcbmV4cG9ydCBjb25zdCBfYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5BdXRvZmlsbEludGVybmFscyA9IHtcbiAgICBzdHViVGhpczogKHZhbHVlKSA9PiB2YWx1ZSxcbn07XG4iLCIvKipcbiAqIENvbnZlcnQgdGhlIGdpdmVuIGFycmF5IGJ1ZmZlciBpbnRvIGEgQmFzZTY0VVJMLWVuY29kZWQgc3RyaW5nLiBJZGVhbCBmb3IgY29udmVydGluZyB2YXJpb3VzXG4gKiBjcmVkZW50aWFsIHJlc3BvbnNlIEFycmF5QnVmZmVycyB0byBzdHJpbmcgZm9yIHNlbmRpbmcgYmFjayB0byB0aGUgc2VydmVyIGFzIEpTT04uXG4gKlxuICogSGVscGVyIG1ldGhvZCB0byBjb21wbGltZW50IGBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlcmBcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKGJ1ZmZlcikge1xuICAgIGNvbnN0IGJ5dGVzID0gbmV3IFVpbnQ4QXJyYXkoYnVmZmVyKTtcbiAgICBsZXQgc3RyID0gJyc7XG4gICAgZm9yIChjb25zdCBjaGFyQ29kZSBvZiBieXRlcykge1xuICAgICAgICBzdHIgKz0gU3RyaW5nLmZyb21DaGFyQ29kZShjaGFyQ29kZSk7XG4gICAgfVxuICAgIGNvbnN0IGJhc2U2NFN0cmluZyA9IGJ0b2Eoc3RyKTtcbiAgICByZXR1cm4gYmFzZTY0U3RyaW5nLnJlcGxhY2UoL1xcKy9nLCAnLScpLnJlcGxhY2UoL1xcLy9nLCAnXycpLnJlcGxhY2UoLz0vZywgJycpO1xufVxuIiwiaW1wb3J0IHsgaXNWYWxpZERvbWFpbiB9IGZyb20gJy4vaXNWYWxpZERvbWFpbi5qcyc7XG5pbXBvcnQgeyBXZWJBdXRobkVycm9yIH0gZnJvbSAnLi93ZWJBdXRobkVycm9yLmpzJztcbi8qKlxuICogQXR0ZW1wdCB0byBpbnR1aXQgX3doeV8gYW4gZXJyb3Igd2FzIHJhaXNlZCBhZnRlciBjYWxsaW5nIGBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuZ2V0KClgXG4gKi9cbmV4cG9ydCBmdW5jdGlvbiBpZGVudGlmeUF1dGhlbnRpY2F0aW9uRXJyb3IoeyBlcnJvciwgb3B0aW9ucywgfSkge1xuICAgIGNvbnN0IHsgcHVibGljS2V5IH0gPSBvcHRpb25zO1xuICAgIGlmICghcHVibGljS2V5KSB7XG4gICAgICAgIHRocm93IEVycm9yKCdvcHRpb25zIHdhcyBtaXNzaW5nIHJlcXVpcmVkIHB1YmxpY0tleSBwcm9wZXJ0eScpO1xuICAgIH1cbiAgICBpZiAoZXJyb3IubmFtZSA9PT0gJ0Fib3J0RXJyb3InKSB7XG4gICAgICAgIGlmIChvcHRpb25zLnNpZ25hbCBpbnN0YW5jZW9mIEFib3J0U2lnbmFsKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDE2KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnQXV0aGVudGljYXRpb24gY2VyZW1vbnkgd2FzIHNlbnQgYW4gYWJvcnQgc2lnbmFsJyxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfQ0VSRU1PTllfQUJPUlRFRCcsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ05vdEFsbG93ZWRFcnJvcicpIHtcbiAgICAgICAgLyoqXG4gICAgICAgICAqIFBhc3MgdGhlIGVycm9yIGRpcmVjdGx5IHRocm91Z2guIFBsYXRmb3JtcyBhcmUgb3ZlcmxvYWRpbmcgdGhpcyBlcnJvciBiZXlvbmQgd2hhdCB0aGUgc3BlY1xuICAgICAgICAgKiBkZWZpbmVzIGFuZCB3ZSBkb24ndCB3YW50IHRvIG92ZXJ3cml0ZSBwb3RlbnRpYWxseSB1c2VmdWwgZXJyb3IgbWVzc2FnZXMuXG4gICAgICAgICAqL1xuICAgICAgICByZXR1cm4gbmV3IFdlYkF1dGhuRXJyb3Ioe1xuICAgICAgICAgICAgbWVzc2FnZTogZXJyb3IubWVzc2FnZSxcbiAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9QQVNTVEhST1VHSF9TRUVfQ0FVU0VfUFJPUEVSVFknLFxuICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ1NlY3VyaXR5RXJyb3InKSB7XG4gICAgICAgIGNvbnN0IGVmZmVjdGl2ZURvbWFpbiA9IGdsb2JhbFRoaXMubG9jYXRpb24uaG9zdG5hbWU7XG4gICAgICAgIGlmICghaXNWYWxpZERvbWFpbihlZmZlY3RpdmVEb21haW4pKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1kaXNjb3Zlci1mcm9tLWV4dGVybmFsLXNvdXJjZSAoU3RlcCA1KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiBgJHtnbG9iYWxUaGlzLmxvY2F0aW9uLmhvc3RuYW1lfSBpcyBhbiBpbnZhbGlkIGRvbWFpbmAsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0lOVkFMSURfRE9NQUlOJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIGlmIChwdWJsaWNLZXkucnBJZCAhPT0gZWZmZWN0aXZlRG9tYWluKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1kaXNjb3Zlci1mcm9tLWV4dGVybmFsLXNvdXJjZSAoU3RlcCA2KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiBgVGhlIFJQIElEIFwiJHtwdWJsaWNLZXkucnBJZH1cIiBpcyBpbnZhbGlkIGZvciB0aGlzIGRvbWFpbmAsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0lOVkFMSURfUlBfSUQnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdVbmtub3duRXJyb3InKSB7XG4gICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLWdldC1hc3NlcnRpb24gKFN0ZXAgMSlcbiAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tb3AtZ2V0LWFzc2VydGlvbiAoU3RlcCAxMilcbiAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgIG1lc3NhZ2U6ICdUaGUgYXV0aGVudGljYXRvciB3YXMgdW5hYmxlIHRvIHByb2Nlc3MgdGhlIHNwZWNpZmllZCBvcHRpb25zLCBvciBjb3VsZCBub3QgY3JlYXRlIGEgbmV3IGFzc2VydGlvbiBzaWduYXR1cmUnLFxuICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVEhFTlRJQ0FUT1JfR0VORVJBTF9FUlJPUicsXG4gICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICByZXR1cm4gZXJyb3I7XG59XG4iLCJpbXBvcnQgeyBpc1ZhbGlkRG9tYWluIH0gZnJvbSAnLi9pc1ZhbGlkRG9tYWluLmpzJztcbmltcG9ydCB7IFdlYkF1dGhuRXJyb3IgfSBmcm9tICcuL3dlYkF1dGhuRXJyb3IuanMnO1xuLyoqXG4gKiBBdHRlbXB0IHRvIGludHVpdCBfd2h5XyBhbiBlcnJvciB3YXMgcmFpc2VkIGFmdGVyIGNhbGxpbmcgYG5hdmlnYXRvci5jcmVkZW50aWFscy5jcmVhdGUoKWBcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGlkZW50aWZ5UmVnaXN0cmF0aW9uRXJyb3IoeyBlcnJvciwgb3B0aW9ucywgfSkge1xuICAgIGNvbnN0IHsgcHVibGljS2V5IH0gPSBvcHRpb25zO1xuICAgIGlmICghcHVibGljS2V5KSB7XG4gICAgICAgIHRocm93IEVycm9yKCdvcHRpb25zIHdhcyBtaXNzaW5nIHJlcXVpcmVkIHB1YmxpY0tleSBwcm9wZXJ0eScpO1xuICAgIH1cbiAgICBpZiAoZXJyb3IubmFtZSA9PT0gJ0Fib3J0RXJyb3InKSB7XG4gICAgICAgIGlmIChvcHRpb25zLnNpZ25hbCBpbnN0YW5jZW9mIEFib3J0U2lnbmFsKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDE2KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnUmVnaXN0cmF0aW9uIGNlcmVtb255IHdhcyBzZW50IGFuIGFib3J0IHNpZ25hbCcsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0NFUkVNT05ZX0FCT1JURUQnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdDb25zdHJhaW50RXJyb3InKSB7XG4gICAgICAgIGlmIChwdWJsaWNLZXkuYXV0aGVudGljYXRvclNlbGVjdGlvbj8ucmVxdWlyZVJlc2lkZW50S2V5ID09PSB0cnVlKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1vcC1tYWtlLWNyZWQgKFN0ZXAgNClcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogJ0Rpc2NvdmVyYWJsZSBjcmVkZW50aWFscyB3ZXJlIHJlcXVpcmVkIGJ1dCBubyBhdmFpbGFibGUgYXV0aGVudGljYXRvciBzdXBwb3J0ZWQgaXQnLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9BVVRIRU5USUNBVE9SX01JU1NJTkdfRElTQ09WRVJBQkxFX0NSRURFTlRJQUxfU1VQUE9SVCcsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAoXG4gICAgICAgIC8vIEB0cy1pZ25vcmU6IGBtZWRpYXRpb25gIGRvZXNuJ3QgeWV0IGV4aXN0IG9uIENyZWRlbnRpYWxDcmVhdGlvbk9wdGlvbnMgYnV0IGl0J3MgcG9zc2libGUgYXMgb2YgU2VwdCAyMDI0XG4gICAgICAgIG9wdGlvbnMubWVkaWF0aW9uID09PSAnY29uZGl0aW9uYWwnICYmXG4gICAgICAgICAgICBwdWJsaWNLZXkuYXV0aGVudGljYXRvclNlbGVjdGlvbj8udXNlclZlcmlmaWNhdGlvbiA9PT0gJ3JlcXVpcmVkJykge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93M2MuZ2l0aHViLmlvL3dlYmF1dGhuLyNzY3RuLWNyZWF0ZUNyZWRlbnRpYWwgKFN0ZXAgMjIuNClcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogJ1VzZXIgdmVyaWZpY2F0aW9uIHdhcyByZXF1aXJlZCBkdXJpbmcgYXV0b21hdGljIHJlZ2lzdHJhdGlvbiBidXQgaXQgY291bGQgbm90IGJlIHBlcmZvcm1lZCcsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVE9fUkVHSVNURVJfVVNFUl9WRVJJRklDQVRJT05fRkFJTFVSRScsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAocHVibGljS2V5LmF1dGhlbnRpY2F0b3JTZWxlY3Rpb24/LnVzZXJWZXJpZmljYXRpb24gPT09ICdyZXF1aXJlZCcpIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLW1ha2UtY3JlZCAoU3RlcCA1KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnVXNlciB2ZXJpZmljYXRpb24gd2FzIHJlcXVpcmVkIGJ1dCBubyBhdmFpbGFibGUgYXV0aGVudGljYXRvciBzdXBwb3J0ZWQgaXQnLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9BVVRIRU5USUNBVE9SX01JU1NJTkdfVVNFUl9WRVJJRklDQVRJT05fU1VQUE9SVCcsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ0ludmFsaWRTdGF0ZUVycm9yJykge1xuICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDIwKVxuICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1vcC1tYWtlLWNyZWQgKFN0ZXAgMylcbiAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgIG1lc3NhZ2U6ICdUaGUgYXV0aGVudGljYXRvciB3YXMgcHJldmlvdXNseSByZWdpc3RlcmVkJyxcbiAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9BVVRIRU5USUNBVE9SX1BSRVZJT1VTTFlfUkVHSVNURVJFRCcsXG4gICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBlbHNlIGlmIChlcnJvci5uYW1lID09PSAnTm90QWxsb3dlZEVycm9yJykge1xuICAgICAgICAvKipcbiAgICAgICAgICogUGFzcyB0aGUgZXJyb3IgZGlyZWN0bHkgdGhyb3VnaC4gUGxhdGZvcm1zIGFyZSBvdmVybG9hZGluZyB0aGlzIGVycm9yIGJleW9uZCB3aGF0IHRoZSBzcGVjXG4gICAgICAgICAqIGRlZmluZXMgYW5kIHdlIGRvbid0IHdhbnQgdG8gb3ZlcndyaXRlIHBvdGVudGlhbGx5IHVzZWZ1bCBlcnJvciBtZXNzYWdlcy5cbiAgICAgICAgICovXG4gICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICBtZXNzYWdlOiBlcnJvci5tZXNzYWdlLFxuICAgICAgICAgICAgY29kZTogJ0VSUk9SX1BBU1NUSFJPVUdIX1NFRV9DQVVTRV9QUk9QRVJUWScsXG4gICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBlbHNlIGlmIChlcnJvci5uYW1lID09PSAnTm90U3VwcG9ydGVkRXJyb3InKSB7XG4gICAgICAgIGNvbnN0IHZhbGlkUHViS2V5Q3JlZFBhcmFtcyA9IHB1YmxpY0tleS5wdWJLZXlDcmVkUGFyYW1zLmZpbHRlcigocGFyYW0pID0+IHBhcmFtLnR5cGUgPT09ICdwdWJsaWMta2V5Jyk7XG4gICAgICAgIGlmICh2YWxpZFB1YktleUNyZWRQYXJhbXMubGVuZ3RoID09PSAwKSB7XG4gICAgICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1jcmVhdGVDcmVkZW50aWFsIChTdGVwIDEwKVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnTm8gZW50cnkgaW4gcHViS2V5Q3JlZFBhcmFtcyB3YXMgb2YgdHlwZSBcInB1YmxpYy1rZXlcIicsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX01BTEZPUk1FRF9QVUJLRVlDUkVEUEFSQU1TJyxcbiAgICAgICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1vcC1tYWtlLWNyZWQgKFN0ZXAgMilcbiAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgIG1lc3NhZ2U6ICdObyBhdmFpbGFibGUgYXV0aGVudGljYXRvciBzdXBwb3J0ZWQgYW55IG9mIHRoZSBzcGVjaWZpZWQgcHViS2V5Q3JlZFBhcmFtcyBhbGdvcml0aG1zJyxcbiAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9BVVRIRU5USUNBVE9SX05PX1NVUFBPUlRFRF9QVUJLRVlDUkVEUEFSQU1TX0FMRycsXG4gICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICBlbHNlIGlmIChlcnJvci5uYW1lID09PSAnU2VjdXJpdHlFcnJvcicpIHtcbiAgICAgICAgY29uc3QgZWZmZWN0aXZlRG9tYWluID0gZ2xvYmFsVGhpcy5sb2NhdGlvbi5ob3N0bmFtZTtcbiAgICAgICAgaWYgKCFpc1ZhbGlkRG9tYWluKGVmZmVjdGl2ZURvbWFpbikpIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLWNyZWF0ZUNyZWRlbnRpYWwgKFN0ZXAgNylcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogYCR7Z2xvYmFsVGhpcy5sb2NhdGlvbi5ob3N0bmFtZX0gaXMgYW4gaW52YWxpZCBkb21haW5gLFxuICAgICAgICAgICAgICAgIGNvZGU6ICdFUlJPUl9JTlZBTElEX0RPTUFJTicsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAocHVibGljS2V5LnJwLmlkICE9PSBlZmZlY3RpdmVEb21haW4pIHtcbiAgICAgICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLWNyZWF0ZUNyZWRlbnRpYWwgKFN0ZXAgOClcbiAgICAgICAgICAgIHJldHVybiBuZXcgV2ViQXV0aG5FcnJvcih7XG4gICAgICAgICAgICAgICAgbWVzc2FnZTogYFRoZSBSUCBJRCBcIiR7cHVibGljS2V5LnJwLmlkfVwiIGlzIGludmFsaWQgZm9yIHRoaXMgZG9tYWluYCxcbiAgICAgICAgICAgICAgICBjb2RlOiAnRVJST1JfSU5WQUxJRF9SUF9JRCcsXG4gICAgICAgICAgICAgICAgY2F1c2U6IGVycm9yLFxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxzZSBpZiAoZXJyb3IubmFtZSA9PT0gJ1R5cGVFcnJvcicpIHtcbiAgICAgICAgaWYgKHB1YmxpY0tleS51c2VyLmlkLmJ5dGVMZW5ndGggPCAxIHx8IHB1YmxpY0tleS51c2VyLmlkLmJ5dGVMZW5ndGggPiA2NCkge1xuICAgICAgICAgICAgLy8gaHR0cHM6Ly93d3cudzMub3JnL1RSL3dlYmF1dGhuLTIvI3NjdG4tY3JlYXRlQ3JlZGVudGlhbCAoU3RlcCA1KVxuICAgICAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgICAgICBtZXNzYWdlOiAnVXNlciBJRCB3YXMgbm90IGJldHdlZW4gMSBhbmQgNjQgY2hhcmFjdGVycycsXG4gICAgICAgICAgICAgICAgY29kZTogJ0VSUk9SX0lOVkFMSURfVVNFUl9JRF9MRU5HVEgnLFxuICAgICAgICAgICAgICAgIGNhdXNlOiBlcnJvcixcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsc2UgaWYgKGVycm9yLm5hbWUgPT09ICdVbmtub3duRXJyb3InKSB7XG4gICAgICAgIC8vIGh0dHBzOi8vd3d3LnczLm9yZy9UUi93ZWJhdXRobi0yLyNzY3RuLW9wLW1ha2UtY3JlZCAoU3RlcCAxKVxuICAgICAgICAvLyBodHRwczovL3d3dy53My5vcmcvVFIvd2ViYXV0aG4tMi8jc2N0bi1vcC1tYWtlLWNyZWQgKFN0ZXAgOClcbiAgICAgICAgcmV0dXJuIG5ldyBXZWJBdXRobkVycm9yKHtcbiAgICAgICAgICAgIG1lc3NhZ2U6ICdUaGUgYXV0aGVudGljYXRvciB3YXMgdW5hYmxlIHRvIHByb2Nlc3MgdGhlIHNwZWNpZmllZCBvcHRpb25zLCBvciBjb3VsZCBub3QgY3JlYXRlIGEgbmV3IGNyZWRlbnRpYWwnLFxuICAgICAgICAgICAgY29kZTogJ0VSUk9SX0FVVEhFTlRJQ0FUT1JfR0VORVJBTF9FUlJPUicsXG4gICAgICAgICAgICBjYXVzZTogZXJyb3IsXG4gICAgICAgIH0pO1xuICAgIH1cbiAgICByZXR1cm4gZXJyb3I7XG59XG4iLCIvKipcbiAqIEEgc2ltcGxlIHRlc3QgdG8gZGV0ZXJtaW5lIGlmIGEgaG9zdG5hbWUgaXMgYSBwcm9wZXJseS1mb3JtYXR0ZWQgZG9tYWluIG5hbWVcbiAqXG4gKiBBIFwidmFsaWQgZG9tYWluXCIgaXMgZGVmaW5lZCBoZXJlOiBodHRwczovL3VybC5zcGVjLndoYXR3Zy5vcmcvI3ZhbGlkLWRvbWFpblxuICpcbiAqIFJlZ2V4IHNvdXJjZWQgZnJvbSBoZXJlOlxuICogaHR0cHM6Ly93d3cub3JlaWxseS5jb20vbGlicmFyeS92aWV3L3JlZ3VsYXItZXhwcmVzc2lvbnMtY29va2Jvb2svOTc4MTQ0OTMyNzQ1My9jaDA4czE1Lmh0bWxcbiAqL1xuZXhwb3J0IGZ1bmN0aW9uIGlzVmFsaWREb21haW4oaG9zdG5hbWUpIHtcbiAgICByZXR1cm4gKFxuICAgIC8vIENvbnNpZGVyIGxvY2FsaG9zdCB2YWxpZCBhcyB3ZWxsIHNpbmNlIGl0J3Mgb2theSB3cnQgU2VjdXJlIENvbnRleHRzXG4gICAgaG9zdG5hbWUgPT09ICdsb2NhbGhvc3QnIHx8XG4gICAgICAgIC9eKFthLXowLTldKygtW2EtejAtOV0rKSpcXC4pK1thLXpdezIsfSQvaS50ZXN0KGhvc3RuYW1lKSk7XG59XG4iLCJpbXBvcnQgeyBicm93c2VyU3VwcG9ydHNXZWJBdXRobiB9IGZyb20gJy4vYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4uanMnO1xuLyoqXG4gKiBEZXRlcm1pbmUgd2hldGhlciB0aGUgYnJvd3NlciBjYW4gY29tbXVuaWNhdGUgd2l0aCBhIGJ1aWx0LWluIGF1dGhlbnRpY2F0b3IsIGxpa2VcbiAqIFRvdWNoIElELCBBbmRyb2lkIGZpbmdlcnByaW50IHNjYW5uZXIsIG9yIFdpbmRvd3MgSGVsbG8uXG4gKlxuICogVGhpcyBtZXRob2Qgd2lsbCBfbm90XyBiZSBhYmxlIHRvIHRlbGwgeW91IHRoZSBuYW1lIG9mIHRoZSBwbGF0Zm9ybSBhdXRoZW50aWNhdG9yLlxuICovXG5leHBvcnQgZnVuY3Rpb24gcGxhdGZvcm1BdXRoZW50aWNhdG9ySXNBdmFpbGFibGUoKSB7XG4gICAgaWYgKCFicm93c2VyU3VwcG9ydHNXZWJBdXRobigpKSB7XG4gICAgICAgIHJldHVybiBuZXcgUHJvbWlzZSgocmVzb2x2ZSkgPT4gcmVzb2x2ZShmYWxzZSkpO1xuICAgIH1cbiAgICByZXR1cm4gUHVibGljS2V5Q3JlZGVudGlhbC5pc1VzZXJWZXJpZnlpbmdQbGF0Zm9ybUF1dGhlbnRpY2F0b3JBdmFpbGFibGUoKTtcbn1cbiIsImNvbnN0IGF0dGFjaG1lbnRzID0gWydjcm9zcy1wbGF0Zm9ybScsICdwbGF0Zm9ybSddO1xuLyoqXG4gKiBJZiBwb3NzaWJsZSBjb2VyY2UgYSBgc3RyaW5nYCB2YWx1ZSBpbnRvIGEga25vd24gYEF1dGhlbnRpY2F0b3JBdHRhY2htZW50YFxuICovXG5leHBvcnQgZnVuY3Rpb24gdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudChhdHRhY2htZW50KSB7XG4gICAgaWYgKCFhdHRhY2htZW50KSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgaWYgKGF0dGFjaG1lbnRzLmluZGV4T2YoYXR0YWNobWVudCkgPCAwKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgcmV0dXJuIGF0dGFjaG1lbnQ7XG59XG4iLCJpbXBvcnQgeyBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlciB9IGZyb20gJy4vYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIuanMnO1xuZXhwb3J0IGZ1bmN0aW9uIHRvUHVibGljS2V5Q3JlZGVudGlhbERlc2NyaXB0b3IoZGVzY3JpcHRvcikge1xuICAgIGNvbnN0IHsgaWQgfSA9IGRlc2NyaXB0b3I7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgLi4uZGVzY3JpcHRvcixcbiAgICAgICAgaWQ6IGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyKGlkKSxcbiAgICAgICAgLyoqXG4gICAgICAgICAqIGBkZXNjcmlwdG9yLnRyYW5zcG9ydHNgIGlzIGFuIGFycmF5IG9mIG91ciBgQXV0aGVudGljYXRvclRyYW5zcG9ydEZ1dHVyZWAgdGhhdCBpbmNsdWRlcyBuZXdlclxuICAgICAgICAgKiB0cmFuc3BvcnRzIHRoYXQgVHlwZVNjcmlwdCdzIERPTSBsaWIgaXMgaWdub3JhbnQgb2YuIENvbnZpbmNlIFRTIHRoYXQgb3VyIGxpc3Qgb2YgdHJhbnNwb3J0c1xuICAgICAgICAgKiBhcmUgZmluZSB0byBwYXNzIHRvIFdlYkF1dGhuIHNpbmNlIGJyb3dzZXJzIHdpbGwgcmVjb2duaXplIHRoZSBuZXcgdmFsdWUuXG4gICAgICAgICAqL1xuICAgICAgICB0cmFuc3BvcnRzOiBkZXNjcmlwdG9yLnRyYW5zcG9ydHMsXG4gICAgfTtcbn1cbiIsImNsYXNzIEJhc2VXZWJBdXRobkFib3J0U2VydmljZSB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0eSh0aGlzLCBcImNvbnRyb2xsZXJcIiwge1xuICAgICAgICAgICAgZW51bWVyYWJsZTogdHJ1ZSxcbiAgICAgICAgICAgIGNvbmZpZ3VyYWJsZTogdHJ1ZSxcbiAgICAgICAgICAgIHdyaXRhYmxlOiB0cnVlLFxuICAgICAgICAgICAgdmFsdWU6IHZvaWQgMFxuICAgICAgICB9KTtcbiAgICB9XG4gICAgY3JlYXRlTmV3QWJvcnRTaWduYWwoKSB7XG4gICAgICAgIC8vIEFib3J0IGFueSBleGlzdGluZyBjYWxscyB0byBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuY3JlYXRlKCkgb3IgbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmdldCgpXG4gICAgICAgIGlmICh0aGlzLmNvbnRyb2xsZXIpIHtcbiAgICAgICAgICAgIGNvbnN0IGFib3J0RXJyb3IgPSBuZXcgRXJyb3IoJ0NhbmNlbGxpbmcgZXhpc3RpbmcgV2ViQXV0aG4gQVBJIGNhbGwgZm9yIG5ldyBvbmUnKTtcbiAgICAgICAgICAgIGFib3J0RXJyb3IubmFtZSA9ICdBYm9ydEVycm9yJztcbiAgICAgICAgICAgIHRoaXMuY29udHJvbGxlci5hYm9ydChhYm9ydEVycm9yKTtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBuZXdDb250cm9sbGVyID0gbmV3IEFib3J0Q29udHJvbGxlcigpO1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIgPSBuZXdDb250cm9sbGVyO1xuICAgICAgICByZXR1cm4gbmV3Q29udHJvbGxlci5zaWduYWw7XG4gICAgfVxuICAgIGNhbmNlbENlcmVtb255KCkge1xuICAgICAgICBpZiAodGhpcy5jb250cm9sbGVyKSB7XG4gICAgICAgICAgICBjb25zdCBhYm9ydEVycm9yID0gbmV3IEVycm9yKCdNYW51YWxseSBjYW5jZWxsaW5nIGV4aXN0aW5nIFdlYkF1dGhuIEFQSSBjYWxsJyk7XG4gICAgICAgICAgICBhYm9ydEVycm9yLm5hbWUgPSAnQWJvcnRFcnJvcic7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIuYWJvcnQoYWJvcnRFcnJvcik7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIgPSB1bmRlZmluZWQ7XG4gICAgICAgIH1cbiAgICB9XG59XG4vKipcbiAqIEEgc2VydmljZSBzaW5nbGV0b24gdG8gaGVscCBlbnN1cmUgdGhhdCBvbmx5IGEgc2luZ2xlIFdlYkF1dGhuIGNlcmVtb255IGlzIGFjdGl2ZSBhdCBhIHRpbWUuXG4gKlxuICogVXNlcnMgb2YgKipAc2ltcGxld2ViYXV0aG4vYnJvd3NlcioqIHNob3VsZG4ndCB0eXBpY2FsbHkgbmVlZCB0byB1c2UgdGhpcywgYnV0IGl0IGNhbiBoZWxwIGUuZy5cbiAqIGRldmVsb3BlcnMgYnVpbGRpbmcgcHJvamVjdHMgdGhhdCB1c2UgY2xpZW50LXNpZGUgcm91dGluZyB0byBiZXR0ZXIgY29udHJvbCB0aGUgYmVoYXZpb3Igb2ZcbiAqIHRoZWlyIFVYIGluIHJlc3BvbnNlIHRvIHJvdXRlciBuYXZpZ2F0aW9uIGV2ZW50cy5cbiAqL1xuZXhwb3J0IGNvbnN0IFdlYkF1dGhuQWJvcnRTZXJ2aWNlID0gbmV3IEJhc2VXZWJBdXRobkFib3J0U2VydmljZSgpO1xuIiwiLyoqXG4gKiBBIGN1c3RvbSBFcnJvciB1c2VkIHRvIHJldHVybiBhIG1vcmUgbnVhbmNlZCBlcnJvciBkZXRhaWxpbmcgX3doeV8gb25lIG9mIHRoZSBlaWdodCBkb2N1bWVudGVkXG4gKiBlcnJvcnMgaW4gdGhlIHNwZWMgd2FzIHJhaXNlZCBhZnRlciBjYWxsaW5nIGBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuY3JlYXRlKClgIG9yXG4gKiBgbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmdldCgpYDpcbiAqXG4gKiAtIGBBYm9ydEVycm9yYFxuICogLSBgQ29uc3RyYWludEVycm9yYFxuICogLSBgSW52YWxpZFN0YXRlRXJyb3JgXG4gKiAtIGBOb3RBbGxvd2VkRXJyb3JgXG4gKiAtIGBOb3RTdXBwb3J0ZWRFcnJvcmBcbiAqIC0gYFNlY3VyaXR5RXJyb3JgXG4gKiAtIGBUeXBlRXJyb3JgXG4gKiAtIGBVbmtub3duRXJyb3JgXG4gKlxuICogRXJyb3IgbWVzc2FnZXMgd2VyZSBkZXRlcm1pbmVkIHRocm91Z2ggaW52ZXN0aWdhdGlvbiBvZiB0aGUgc3BlYyB0byBkZXRlcm1pbmUgdW5kZXIgd2hpY2hcbiAqIHNjZW5hcmlvcyBhIGdpdmVuIGVycm9yIHdvdWxkIGJlIHJhaXNlZC5cbiAqL1xuZXhwb3J0IGNsYXNzIFdlYkF1dGhuRXJyb3IgZXh0ZW5kcyBFcnJvciB7XG4gICAgY29uc3RydWN0b3IoeyBtZXNzYWdlLCBjb2RlLCBjYXVzZSwgbmFtZSwgfSkge1xuICAgICAgICAvLyBAdHMtaWdub3JlOiBoZWxwIFJvbGx1cCB1bmRlcnN0YW5kIHRoYXQgYGNhdXNlYCBpcyBva2F5IHRvIHNldFxuICAgICAgICBzdXBlcihtZXNzYWdlLCB7IGNhdXNlIH0pO1xuICAgICAgICBPYmplY3QuZGVmaW5lUHJvcGVydHkodGhpcywgXCJjb2RlXCIsIHtcbiAgICAgICAgICAgIGVudW1lcmFibGU6IHRydWUsXG4gICAgICAgICAgICBjb25maWd1cmFibGU6IHRydWUsXG4gICAgICAgICAgICB3cml0YWJsZTogdHJ1ZSxcbiAgICAgICAgICAgIHZhbHVlOiB2b2lkIDBcbiAgICAgICAgfSk7XG4gICAgICAgIHRoaXMubmFtZSA9IG5hbWUgPz8gY2F1c2UubmFtZTtcbiAgICAgICAgdGhpcy5jb2RlID0gY29kZTtcbiAgICB9XG59XG4iLCJleHBvcnQgKiBmcm9tICcuL21ldGhvZHMvc3RhcnRSZWdpc3RyYXRpb24uanMnO1xuZXhwb3J0ICogZnJvbSAnLi9tZXRob2RzL3N0YXJ0QXV0aGVudGljYXRpb24uanMnO1xuZXhwb3J0ICogZnJvbSAnLi9oZWxwZXJzL2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuLmpzJztcbmV4cG9ydCAqIGZyb20gJy4vaGVscGVycy9wbGF0Zm9ybUF1dGhlbnRpY2F0b3JJc0F2YWlsYWJsZS5qcyc7XG5leHBvcnQgKiBmcm9tICcuL2hlbHBlcnMvYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5BdXRvZmlsbC5qcyc7XG5leHBvcnQgKiBmcm9tICcuL2hlbHBlcnMvYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIuanMnO1xuZXhwb3J0ICogZnJvbSAnLi9oZWxwZXJzL2J1ZmZlclRvQmFzZTY0VVJMU3RyaW5nLmpzJztcbmV4cG9ydCAqIGZyb20gJy4vaGVscGVycy93ZWJBdXRobkFib3J0U2VydmljZS5qcyc7XG5leHBvcnQgKiBmcm9tICcuL2hlbHBlcnMvd2ViQXV0aG5FcnJvci5qcyc7XG5leHBvcnQgKiBmcm9tICcuL3R5cGVzL2luZGV4LmpzJztcbiIsImltcG9ydCB7IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nIH0gZnJvbSAnLi4vaGVscGVycy9idWZmZXJUb0Jhc2U2NFVSTFN0cmluZy5qcyc7XG5pbXBvcnQgeyBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlciB9IGZyb20gJy4uL2hlbHBlcnMvYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIuanMnO1xuaW1wb3J0IHsgYnJvd3NlclN1cHBvcnRzV2ViQXV0aG4gfSBmcm9tICcuLi9oZWxwZXJzL2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuLmpzJztcbmltcG9ydCB7IGJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuQXV0b2ZpbGwgfSBmcm9tICcuLi9oZWxwZXJzL2Jyb3dzZXJTdXBwb3J0c1dlYkF1dGhuQXV0b2ZpbGwuanMnO1xuaW1wb3J0IHsgdG9QdWJsaWNLZXlDcmVkZW50aWFsRGVzY3JpcHRvciB9IGZyb20gJy4uL2hlbHBlcnMvdG9QdWJsaWNLZXlDcmVkZW50aWFsRGVzY3JpcHRvci5qcyc7XG5pbXBvcnQgeyBpZGVudGlmeUF1dGhlbnRpY2F0aW9uRXJyb3IgfSBmcm9tICcuLi9oZWxwZXJzL2lkZW50aWZ5QXV0aGVudGljYXRpb25FcnJvci5qcyc7XG5pbXBvcnQgeyBXZWJBdXRobkFib3J0U2VydmljZSB9IGZyb20gJy4uL2hlbHBlcnMvd2ViQXV0aG5BYm9ydFNlcnZpY2UuanMnO1xuaW1wb3J0IHsgdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudCB9IGZyb20gJy4uL2hlbHBlcnMvdG9BdXRoZW50aWNhdG9yQXR0YWNobWVudC5qcyc7XG4vKipcbiAqIEJlZ2luIGF1dGhlbnRpY2F0b3IgXCJsb2dpblwiIHZpYSBXZWJBdXRobiBhc3NlcnRpb25cbiAqXG4gKiBAcGFyYW0gb3B0aW9uc0pTT04gT3V0cHV0IGZyb20gKipAc2ltcGxld2ViYXV0aG4vc2VydmVyKioncyBgZ2VuZXJhdGVBdXRoZW50aWNhdGlvbk9wdGlvbnMoKWBcbiAqIEBwYXJhbSB1c2VCcm93c2VyQXV0b2ZpbGwgKE9wdGlvbmFsKSBJbml0aWFsaXplIGNvbmRpdGlvbmFsIFVJIHRvIGVuYWJsZSBsb2dnaW5nIGluIHZpYSBicm93c2VyIGF1dG9maWxsIHByb21wdHMuIERlZmF1bHRzIHRvIGBmYWxzZWAuXG4gKiBAcGFyYW0gdmVyaWZ5QnJvd3NlckF1dG9maWxsSW5wdXQgKE9wdGlvbmFsKSBFbnN1cmUgYSBzdWl0YWJsZSBgPGlucHV0PmAgZWxlbWVudCBpcyBwcmVzZW50IHdoZW4gYHVzZUJyb3dzZXJBdXRvZmlsbGAgaXMgYHRydWVgLiBEZWZhdWx0cyB0byBgdHJ1ZWAuXG4gKi9cbmV4cG9ydCBhc3luYyBmdW5jdGlvbiBzdGFydEF1dGhlbnRpY2F0aW9uKG9wdGlvbnMpIHtcbiAgICAvLyBAdHMtaWdub3JlOiBJbnRlbnRpb25hbGx5IGNoZWNrIGZvciBvbGQgY2FsbCBzdHJ1Y3R1cmUgdG8gd2FybiBhYm91dCBpbXByb3BlciBBUEkgY2FsbFxuICAgIGlmICghb3B0aW9ucy5vcHRpb25zSlNPTiAmJiBvcHRpb25zLmNoYWxsZW5nZSkge1xuICAgICAgICBjb25zb2xlLndhcm4oJ3N0YXJ0QXV0aGVudGljYXRpb24oKSB3YXMgbm90IGNhbGxlZCBjb3JyZWN0bHkuIEl0IHdpbGwgdHJ5IHRvIGNvbnRpbnVlIHdpdGggdGhlIHByb3ZpZGVkIG9wdGlvbnMsIGJ1dCB0aGlzIGNhbGwgc2hvdWxkIGJlIHJlZmFjdG9yZWQgdG8gdXNlIHRoZSBleHBlY3RlZCBjYWxsIHN0cnVjdHVyZSBpbnN0ZWFkLiBTZWUgaHR0cHM6Ly9zaW1wbGV3ZWJhdXRobi5kZXYvZG9jcy9wYWNrYWdlcy9icm93c2VyI3R5cGVlcnJvci1jYW5ub3QtcmVhZC1wcm9wZXJ0aWVzLW9mLXVuZGVmaW5lZC1yZWFkaW5nLWNoYWxsZW5nZSBmb3IgbW9yZSBpbmZvcm1hdGlvbi4nKTtcbiAgICAgICAgLy8gQHRzLWlnbm9yZTogUmVhc3NpZ24gdGhlIG9wdGlvbnMsIHBhc3NlZCBpbiBhcyBhIHBvc2l0aW9uYWwgYXJndW1lbnQsIHRvIHRoZSBleHBlY3RlZCB2YXJpYWJsZVxuICAgICAgICBvcHRpb25zID0geyBvcHRpb25zSlNPTjogb3B0aW9ucyB9O1xuICAgIH1cbiAgICBjb25zdCB7IG9wdGlvbnNKU09OLCB1c2VCcm93c2VyQXV0b2ZpbGwgPSBmYWxzZSwgdmVyaWZ5QnJvd3NlckF1dG9maWxsSW5wdXQgPSB0cnVlLCB9ID0gb3B0aW9ucztcbiAgICBpZiAoIWJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuKCkpIHtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdXZWJBdXRobiBpcyBub3Qgc3VwcG9ydGVkIGluIHRoaXMgYnJvd3NlcicpO1xuICAgIH1cbiAgICAvLyBXZSBuZWVkIHRvIGF2b2lkIHBhc3NpbmcgZW1wdHkgYXJyYXkgdG8gYXZvaWQgYmxvY2tpbmcgcmV0cmlldmFsXG4gICAgLy8gb2YgcHVibGljIGtleVxuICAgIGxldCBhbGxvd0NyZWRlbnRpYWxzO1xuICAgIGlmIChvcHRpb25zSlNPTi5hbGxvd0NyZWRlbnRpYWxzPy5sZW5ndGggIT09IDApIHtcbiAgICAgICAgYWxsb3dDcmVkZW50aWFscyA9IG9wdGlvbnNKU09OLmFsbG93Q3JlZGVudGlhbHM/Lm1hcCh0b1B1YmxpY0tleUNyZWRlbnRpYWxEZXNjcmlwdG9yKTtcbiAgICB9XG4gICAgLy8gV2UgbmVlZCB0byBjb252ZXJ0IHNvbWUgdmFsdWVzIHRvIFVpbnQ4QXJyYXlzIGJlZm9yZSBwYXNzaW5nIHRoZSBjcmVkZW50aWFscyB0byB0aGUgbmF2aWdhdG9yXG4gICAgY29uc3QgcHVibGljS2V5ID0ge1xuICAgICAgICAuLi5vcHRpb25zSlNPTixcbiAgICAgICAgY2hhbGxlbmdlOiBiYXNlNjRVUkxTdHJpbmdUb0J1ZmZlcihvcHRpb25zSlNPTi5jaGFsbGVuZ2UpLFxuICAgICAgICBhbGxvd0NyZWRlbnRpYWxzLFxuICAgIH07XG4gICAgLy8gUHJlcGFyZSBvcHRpb25zIGZvciBgLmdldCgpYFxuICAgIGNvbnN0IGdldE9wdGlvbnMgPSB7fTtcbiAgICAvKipcbiAgICAgKiBTZXQgdXAgdGhlIHBhZ2UgdG8gcHJvbXB0IHRoZSB1c2VyIHRvIHNlbGVjdCBhIGNyZWRlbnRpYWwgZm9yIGF1dGhlbnRpY2F0aW9uIHZpYSB0aGUgYnJvd3NlcidzXG4gICAgICogaW5wdXQgYXV0b2ZpbGwgbWVjaGFuaXNtLlxuICAgICAqL1xuICAgIGlmICh1c2VCcm93c2VyQXV0b2ZpbGwpIHtcbiAgICAgICAgaWYgKCEoYXdhaXQgYnJvd3NlclN1cHBvcnRzV2ViQXV0aG5BdXRvZmlsbCgpKSkge1xuICAgICAgICAgICAgdGhyb3cgRXJyb3IoJ0Jyb3dzZXIgZG9lcyBub3Qgc3VwcG9ydCBXZWJBdXRobiBhdXRvZmlsbCcpO1xuICAgICAgICB9XG4gICAgICAgIC8vIENoZWNrIGZvciBhbiA8aW5wdXQ+IHdpdGggXCJ3ZWJhdXRoblwiIGluIGl0cyBgYXV0b2NvbXBsZXRlYCBhdHRyaWJ1dGVcbiAgICAgICAgY29uc3QgZWxpZ2libGVJbnB1dHMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKFwiaW5wdXRbYXV0b2NvbXBsZXRlJD0nd2ViYXV0aG4nXVwiKTtcbiAgICAgICAgLy8gV2ViQXV0aG4gYXV0b2ZpbGwgcmVxdWlyZXMgYXQgbGVhc3Qgb25lIHZhbGlkIGlucHV0XG4gICAgICAgIGlmIChlbGlnaWJsZUlucHV0cy5sZW5ndGggPCAxICYmIHZlcmlmeUJyb3dzZXJBdXRvZmlsbElucHV0KSB7XG4gICAgICAgICAgICB0aHJvdyBFcnJvcignTm8gPGlucHV0PiB3aXRoIFwid2ViYXV0aG5cIiBhcyB0aGUgb25seSBvciBsYXN0IHZhbHVlIGluIGl0cyBgYXV0b2NvbXBsZXRlYCBhdHRyaWJ1dGUgd2FzIGRldGVjdGVkJyk7XG4gICAgICAgIH1cbiAgICAgICAgLy8gYENyZWRlbnRpYWxNZWRpYXRpb25SZXF1aXJlbWVudGAgZG9lc24ndCBrbm93IGFib3V0IFwiY29uZGl0aW9uYWxcIiB5ZXQgYXMgb2ZcbiAgICAgICAgLy8gdHlwZXNjcmlwdEA0LjYuM1xuICAgICAgICBnZXRPcHRpb25zLm1lZGlhdGlvbiA9ICdjb25kaXRpb25hbCc7XG4gICAgICAgIC8vIENvbmRpdGlvbmFsIFVJIHJlcXVpcmVzIGFuIGVtcHR5IGFsbG93IGxpc3RcbiAgICAgICAgcHVibGljS2V5LmFsbG93Q3JlZGVudGlhbHMgPSBbXTtcbiAgICB9XG4gICAgLy8gRmluYWxpemUgb3B0aW9uc1xuICAgIGdldE9wdGlvbnMucHVibGljS2V5ID0gcHVibGljS2V5O1xuICAgIC8vIFNldCB1cCB0aGUgYWJpbGl0eSB0byBjYW5jZWwgdGhpcyByZXF1ZXN0IGlmIHRoZSB1c2VyIGF0dGVtcHRzIGFub3RoZXJcbiAgICBnZXRPcHRpb25zLnNpZ25hbCA9IFdlYkF1dGhuQWJvcnRTZXJ2aWNlLmNyZWF0ZU5ld0Fib3J0U2lnbmFsKCk7XG4gICAgLy8gV2FpdCBmb3IgdGhlIHVzZXIgdG8gY29tcGxldGUgYXNzZXJ0aW9uXG4gICAgbGV0IGNyZWRlbnRpYWw7XG4gICAgdHJ5IHtcbiAgICAgICAgY3JlZGVudGlhbCA9IChhd2FpdCBuYXZpZ2F0b3IuY3JlZGVudGlhbHMuZ2V0KGdldE9wdGlvbnMpKTtcbiAgICB9XG4gICAgY2F0Y2ggKGVycikge1xuICAgICAgICB0aHJvdyBpZGVudGlmeUF1dGhlbnRpY2F0aW9uRXJyb3IoeyBlcnJvcjogZXJyLCBvcHRpb25zOiBnZXRPcHRpb25zIH0pO1xuICAgIH1cbiAgICBpZiAoIWNyZWRlbnRpYWwpIHtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdBdXRoZW50aWNhdGlvbiB3YXMgbm90IGNvbXBsZXRlZCcpO1xuICAgIH1cbiAgICBjb25zdCB7IGlkLCByYXdJZCwgcmVzcG9uc2UsIHR5cGUgfSA9IGNyZWRlbnRpYWw7XG4gICAgbGV0IHVzZXJIYW5kbGUgPSB1bmRlZmluZWQ7XG4gICAgaWYgKHJlc3BvbnNlLnVzZXJIYW5kbGUpIHtcbiAgICAgICAgdXNlckhhbmRsZSA9IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJlc3BvbnNlLnVzZXJIYW5kbGUpO1xuICAgIH1cbiAgICAvLyBDb252ZXJ0IHZhbHVlcyB0byBiYXNlNjQgdG8gbWFrZSBpdCBlYXNpZXIgdG8gc2VuZCBiYWNrIHRvIHRoZSBzZXJ2ZXJcbiAgICByZXR1cm4ge1xuICAgICAgICBpZCxcbiAgICAgICAgcmF3SWQ6IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJhd0lkKSxcbiAgICAgICAgcmVzcG9uc2U6IHtcbiAgICAgICAgICAgIGF1dGhlbnRpY2F0b3JEYXRhOiBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhyZXNwb25zZS5hdXRoZW50aWNhdG9yRGF0YSksXG4gICAgICAgICAgICBjbGllbnREYXRhSlNPTjogYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcocmVzcG9uc2UuY2xpZW50RGF0YUpTT04pLFxuICAgICAgICAgICAgc2lnbmF0dXJlOiBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhyZXNwb25zZS5zaWduYXR1cmUpLFxuICAgICAgICAgICAgdXNlckhhbmRsZSxcbiAgICAgICAgfSxcbiAgICAgICAgdHlwZSxcbiAgICAgICAgY2xpZW50RXh0ZW5zaW9uUmVzdWx0czogY3JlZGVudGlhbC5nZXRDbGllbnRFeHRlbnNpb25SZXN1bHRzKCksXG4gICAgICAgIGF1dGhlbnRpY2F0b3JBdHRhY2htZW50OiB0b0F1dGhlbnRpY2F0b3JBdHRhY2htZW50KGNyZWRlbnRpYWwuYXV0aGVudGljYXRvckF0dGFjaG1lbnQpLFxuICAgIH07XG59XG4iLCJpbXBvcnQgeyBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyB9IGZyb20gJy4uL2hlbHBlcnMvYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcuanMnO1xuaW1wb3J0IHsgYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIgfSBmcm9tICcuLi9oZWxwZXJzL2Jhc2U2NFVSTFN0cmluZ1RvQnVmZmVyLmpzJztcbmltcG9ydCB7IGJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuIH0gZnJvbSAnLi4vaGVscGVycy9icm93c2VyU3VwcG9ydHNXZWJBdXRobi5qcyc7XG5pbXBvcnQgeyB0b1B1YmxpY0tleUNyZWRlbnRpYWxEZXNjcmlwdG9yIH0gZnJvbSAnLi4vaGVscGVycy90b1B1YmxpY0tleUNyZWRlbnRpYWxEZXNjcmlwdG9yLmpzJztcbmltcG9ydCB7IGlkZW50aWZ5UmVnaXN0cmF0aW9uRXJyb3IgfSBmcm9tICcuLi9oZWxwZXJzL2lkZW50aWZ5UmVnaXN0cmF0aW9uRXJyb3IuanMnO1xuaW1wb3J0IHsgV2ViQXV0aG5BYm9ydFNlcnZpY2UgfSBmcm9tICcuLi9oZWxwZXJzL3dlYkF1dGhuQWJvcnRTZXJ2aWNlLmpzJztcbmltcG9ydCB7IHRvQXV0aGVudGljYXRvckF0dGFjaG1lbnQgfSBmcm9tICcuLi9oZWxwZXJzL3RvQXV0aGVudGljYXRvckF0dGFjaG1lbnQuanMnO1xuLyoqXG4gKiBCZWdpbiBhdXRoZW50aWNhdG9yIFwicmVnaXN0cmF0aW9uXCIgdmlhIFdlYkF1dGhuIGF0dGVzdGF0aW9uXG4gKlxuICogQHBhcmFtIG9wdGlvbnNKU09OIE91dHB1dCBmcm9tICoqQHNpbXBsZXdlYmF1dGhuL3NlcnZlcioqJ3MgYGdlbmVyYXRlUmVnaXN0cmF0aW9uT3B0aW9ucygpYFxuICogQHBhcmFtIHVzZUF1dG9SZWdpc3RlciAoT3B0aW9uYWwpIFRyeSB0byBzaWxlbnRseSBjcmVhdGUgYSBwYXNza2V5IHdpdGggdGhlIHBhc3N3b3JkIG1hbmFnZXIgdGhhdCB0aGUgdXNlciBqdXN0IHNpZ25lZCBpbiB3aXRoLiBEZWZhdWx0cyB0byBgZmFsc2VgLlxuICovXG5leHBvcnQgYXN5bmMgZnVuY3Rpb24gc3RhcnRSZWdpc3RyYXRpb24ob3B0aW9ucykge1xuICAgIC8vIEB0cy1pZ25vcmU6IEludGVudGlvbmFsbHkgY2hlY2sgZm9yIG9sZCBjYWxsIHN0cnVjdHVyZSB0byB3YXJuIGFib3V0IGltcHJvcGVyIEFQSSBjYWxsXG4gICAgaWYgKCFvcHRpb25zLm9wdGlvbnNKU09OICYmIG9wdGlvbnMuY2hhbGxlbmdlKSB7XG4gICAgICAgIGNvbnNvbGUud2Fybignc3RhcnRSZWdpc3RyYXRpb24oKSB3YXMgbm90IGNhbGxlZCBjb3JyZWN0bHkuIEl0IHdpbGwgdHJ5IHRvIGNvbnRpbnVlIHdpdGggdGhlIHByb3ZpZGVkIG9wdGlvbnMsIGJ1dCB0aGlzIGNhbGwgc2hvdWxkIGJlIHJlZmFjdG9yZWQgdG8gdXNlIHRoZSBleHBlY3RlZCBjYWxsIHN0cnVjdHVyZSBpbnN0ZWFkLiBTZWUgaHR0cHM6Ly9zaW1wbGV3ZWJhdXRobi5kZXYvZG9jcy9wYWNrYWdlcy9icm93c2VyI3R5cGVlcnJvci1jYW5ub3QtcmVhZC1wcm9wZXJ0aWVzLW9mLXVuZGVmaW5lZC1yZWFkaW5nLWNoYWxsZW5nZSBmb3IgbW9yZSBpbmZvcm1hdGlvbi4nKTtcbiAgICAgICAgLy8gQHRzLWlnbm9yZTogUmVhc3NpZ24gdGhlIG9wdGlvbnMsIHBhc3NlZCBpbiBhcyBhIHBvc2l0aW9uYWwgYXJndW1lbnQsIHRvIHRoZSBleHBlY3RlZCB2YXJpYWJsZVxuICAgICAgICBvcHRpb25zID0geyBvcHRpb25zSlNPTjogb3B0aW9ucyB9O1xuICAgIH1cbiAgICBjb25zdCB7IG9wdGlvbnNKU09OLCB1c2VBdXRvUmVnaXN0ZXIgPSBmYWxzZSB9ID0gb3B0aW9ucztcbiAgICBpZiAoIWJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuKCkpIHtcbiAgICAgICAgdGhyb3cgbmV3IEVycm9yKCdXZWJBdXRobiBpcyBub3Qgc3VwcG9ydGVkIGluIHRoaXMgYnJvd3NlcicpO1xuICAgIH1cbiAgICAvLyBXZSBuZWVkIHRvIGNvbnZlcnQgc29tZSB2YWx1ZXMgdG8gVWludDhBcnJheXMgYmVmb3JlIHBhc3NpbmcgdGhlIGNyZWRlbnRpYWxzIHRvIHRoZSBuYXZpZ2F0b3JcbiAgICBjb25zdCBwdWJsaWNLZXkgPSB7XG4gICAgICAgIC4uLm9wdGlvbnNKU09OLFxuICAgICAgICBjaGFsbGVuZ2U6IGJhc2U2NFVSTFN0cmluZ1RvQnVmZmVyKG9wdGlvbnNKU09OLmNoYWxsZW5nZSksXG4gICAgICAgIHVzZXI6IHtcbiAgICAgICAgICAgIC4uLm9wdGlvbnNKU09OLnVzZXIsXG4gICAgICAgICAgICBpZDogYmFzZTY0VVJMU3RyaW5nVG9CdWZmZXIob3B0aW9uc0pTT04udXNlci5pZCksXG4gICAgICAgIH0sXG4gICAgICAgIGV4Y2x1ZGVDcmVkZW50aWFsczogb3B0aW9uc0pTT04uZXhjbHVkZUNyZWRlbnRpYWxzPy5tYXAodG9QdWJsaWNLZXlDcmVkZW50aWFsRGVzY3JpcHRvciksXG4gICAgfTtcbiAgICAvLyBQcmVwYXJlIG9wdGlvbnMgZm9yIGAuY3JlYXRlKClgXG4gICAgY29uc3QgY3JlYXRlT3B0aW9ucyA9IHt9O1xuICAgIC8qKlxuICAgICAqIFRyeSB0byB1c2UgY29uZGl0aW9uYWwgY3JlYXRlIHRvIHJlZ2lzdGVyIGEgcGFzc2tleSBmb3IgdGhlIHVzZXIgd2l0aCB0aGUgcGFzc3dvcmQgbWFuYWdlclxuICAgICAqIHRoZSB1c2VyIGp1c3QgdXNlZCB0byBhdXRoZW50aWNhdGUgd2l0aC4gVGhlIHVzZXIgd29uJ3QgYmUgc2hvd24gYW55IHByb21pbmVudCBVSSBieSB0aGVcbiAgICAgKiBicm93c2VyLlxuICAgICAqL1xuICAgIGlmICh1c2VBdXRvUmVnaXN0ZXIpIHtcbiAgICAgICAgLy8gQHRzLWlnbm9yZTogYG1lZGlhdGlvbmAgZG9lc24ndCB5ZXQgZXhpc3Qgb24gQ3JlZGVudGlhbENyZWF0aW9uT3B0aW9ucyBidXQgaXQncyBwb3NzaWJsZSBhcyBvZiBTZXB0IDIwMjRcbiAgICAgICAgY3JlYXRlT3B0aW9ucy5tZWRpYXRpb24gPSAnY29uZGl0aW9uYWwnO1xuICAgIH1cbiAgICAvLyBGaW5hbGl6ZSBvcHRpb25zXG4gICAgY3JlYXRlT3B0aW9ucy5wdWJsaWNLZXkgPSBwdWJsaWNLZXk7XG4gICAgLy8gU2V0IHVwIHRoZSBhYmlsaXR5IHRvIGNhbmNlbCB0aGlzIHJlcXVlc3QgaWYgdGhlIHVzZXIgYXR0ZW1wdHMgYW5vdGhlclxuICAgIGNyZWF0ZU9wdGlvbnMuc2lnbmFsID0gV2ViQXV0aG5BYm9ydFNlcnZpY2UuY3JlYXRlTmV3QWJvcnRTaWduYWwoKTtcbiAgICAvLyBXYWl0IGZvciB0aGUgdXNlciB0byBjb21wbGV0ZSBhdHRlc3RhdGlvblxuICAgIGxldCBjcmVkZW50aWFsO1xuICAgIHRyeSB7XG4gICAgICAgIGNyZWRlbnRpYWwgPSAoYXdhaXQgbmF2aWdhdG9yLmNyZWRlbnRpYWxzLmNyZWF0ZShjcmVhdGVPcHRpb25zKSk7XG4gICAgfVxuICAgIGNhdGNoIChlcnIpIHtcbiAgICAgICAgdGhyb3cgaWRlbnRpZnlSZWdpc3RyYXRpb25FcnJvcih7IGVycm9yOiBlcnIsIG9wdGlvbnM6IGNyZWF0ZU9wdGlvbnMgfSk7XG4gICAgfVxuICAgIGlmICghY3JlZGVudGlhbCkge1xuICAgICAgICB0aHJvdyBuZXcgRXJyb3IoJ1JlZ2lzdHJhdGlvbiB3YXMgbm90IGNvbXBsZXRlZCcpO1xuICAgIH1cbiAgICBjb25zdCB7IGlkLCByYXdJZCwgcmVzcG9uc2UsIHR5cGUgfSA9IGNyZWRlbnRpYWw7XG4gICAgLy8gQ29udGludWUgdG8gcGxheSBpdCBzYWZlIHdpdGggYGdldFRyYW5zcG9ydHMoKWAgZm9yIG5vdywgZXZlbiB3aGVuIEwzIHR5cGVzIHNheSBpdCdzIHJlcXVpcmVkXG4gICAgbGV0IHRyYW5zcG9ydHMgPSB1bmRlZmluZWQ7XG4gICAgaWYgKHR5cGVvZiByZXNwb25zZS5nZXRUcmFuc3BvcnRzID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgIHRyYW5zcG9ydHMgPSByZXNwb25zZS5nZXRUcmFuc3BvcnRzKCk7XG4gICAgfVxuICAgIC8vIEwzIHNheXMgdGhpcyBpcyByZXF1aXJlZCwgYnV0IGJyb3dzZXIgYW5kIHdlYnZpZXcgc3VwcG9ydCBhcmUgc3RpbGwgbm90IGd1YXJhbnRlZWQuXG4gICAgbGV0IHJlc3BvbnNlUHVibGljS2V5QWxnb3JpdGhtID0gdW5kZWZpbmVkO1xuICAgIGlmICh0eXBlb2YgcmVzcG9uc2UuZ2V0UHVibGljS2V5QWxnb3JpdGhtID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICByZXNwb25zZVB1YmxpY0tleUFsZ29yaXRobSA9IHJlc3BvbnNlLmdldFB1YmxpY0tleUFsZ29yaXRobSgpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgd2Fybk9uQnJva2VuSW1wbGVtZW50YXRpb24oJ2dldFB1YmxpY0tleUFsZ29yaXRobSgpJywgZXJyb3IpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGxldCByZXNwb25zZVB1YmxpY0tleSA9IHVuZGVmaW5lZDtcbiAgICBpZiAodHlwZW9mIHJlc3BvbnNlLmdldFB1YmxpY0tleSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgY29uc3QgX3B1YmxpY0tleSA9IHJlc3BvbnNlLmdldFB1YmxpY0tleSgpO1xuICAgICAgICAgICAgaWYgKF9wdWJsaWNLZXkgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICByZXNwb25zZVB1YmxpY0tleSA9IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKF9wdWJsaWNLZXkpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgd2Fybk9uQnJva2VuSW1wbGVtZW50YXRpb24oJ2dldFB1YmxpY0tleSgpJywgZXJyb3IpO1xuICAgICAgICB9XG4gICAgfVxuICAgIC8vIEwzIHNheXMgdGhpcyBpcyByZXF1aXJlZCwgYnV0IGJyb3dzZXIgYW5kIHdlYnZpZXcgc3VwcG9ydCBhcmUgc3RpbGwgbm90IGd1YXJhbnRlZWQuXG4gICAgbGV0IHJlc3BvbnNlQXV0aGVudGljYXRvckRhdGE7XG4gICAgaWYgKHR5cGVvZiByZXNwb25zZS5nZXRBdXRoZW50aWNhdG9yRGF0YSA9PT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgcmVzcG9uc2VBdXRoZW50aWNhdG9yRGF0YSA9IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJlc3BvbnNlLmdldEF1dGhlbnRpY2F0b3JEYXRhKCkpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgd2Fybk9uQnJva2VuSW1wbGVtZW50YXRpb24oJ2dldEF1dGhlbnRpY2F0b3JEYXRhKCknLCBlcnJvcik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmV0dXJuIHtcbiAgICAgICAgaWQsXG4gICAgICAgIHJhd0lkOiBidWZmZXJUb0Jhc2U2NFVSTFN0cmluZyhyYXdJZCksXG4gICAgICAgIHJlc3BvbnNlOiB7XG4gICAgICAgICAgICBhdHRlc3RhdGlvbk9iamVjdDogYnVmZmVyVG9CYXNlNjRVUkxTdHJpbmcocmVzcG9uc2UuYXR0ZXN0YXRpb25PYmplY3QpLFxuICAgICAgICAgICAgY2xpZW50RGF0YUpTT046IGJ1ZmZlclRvQmFzZTY0VVJMU3RyaW5nKHJlc3BvbnNlLmNsaWVudERhdGFKU09OKSxcbiAgICAgICAgICAgIHRyYW5zcG9ydHMsXG4gICAgICAgICAgICBwdWJsaWNLZXlBbGdvcml0aG06IHJlc3BvbnNlUHVibGljS2V5QWxnb3JpdGhtLFxuICAgICAgICAgICAgcHVibGljS2V5OiByZXNwb25zZVB1YmxpY0tleSxcbiAgICAgICAgICAgIGF1dGhlbnRpY2F0b3JEYXRhOiByZXNwb25zZUF1dGhlbnRpY2F0b3JEYXRhLFxuICAgICAgICB9LFxuICAgICAgICB0eXBlLFxuICAgICAgICBjbGllbnRFeHRlbnNpb25SZXN1bHRzOiBjcmVkZW50aWFsLmdldENsaWVudEV4dGVuc2lvblJlc3VsdHMoKSxcbiAgICAgICAgYXV0aGVudGljYXRvckF0dGFjaG1lbnQ6IHRvQXV0aGVudGljYXRvckF0dGFjaG1lbnQoY3JlZGVudGlhbC5hdXRoZW50aWNhdG9yQXR0YWNobWVudCksXG4gICAgfTtcbn1cbi8qKlxuICogVmlzaWJseSB3YXJuIHdoZW4gd2UgZGV0ZWN0IGFuIGlzc3VlIHJlbGF0ZWQgdG8gYSBwYXNza2V5IHByb3ZpZGVyIGludGVyY2VwdGluZyBXZWJBdXRobiBBUElcbiAqIGNhbGxzXG4gKi9cbmZ1bmN0aW9uIHdhcm5PbkJyb2tlbkltcGxlbWVudGF0aW9uKG1ldGhvZE5hbWUsIGNhdXNlKSB7XG4gICAgY29uc29sZS53YXJuKGBUaGUgYnJvd3NlciBleHRlbnNpb24gdGhhdCBpbnRlcmNlcHRlZCB0aGlzIFdlYkF1dGhuIEFQSSBjYWxsIGluY29ycmVjdGx5IGltcGxlbWVudGVkICR7bWV0aG9kTmFtZX0uIFlvdSBzaG91bGQgcmVwb3J0IHRoaXMgZXJyb3IgdG8gdGhlbS5cXG5gLCBjYXVzZSk7XG59XG4iLCJleHBvcnQge307XG4iLCIvLyBUaGUgbW9kdWxlIGNhY2hlXG52YXIgX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fID0ge307XG5cbi8vIFRoZSByZXF1aXJlIGZ1bmN0aW9uXG5mdW5jdGlvbiBfX3dlYnBhY2tfcmVxdWlyZV9fKG1vZHVsZUlkKSB7XG5cdC8vIENoZWNrIGlmIG1vZHVsZSBpcyBpbiBjYWNoZVxuXHR2YXIgY2FjaGVkTW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXTtcblx0aWYgKGNhY2hlZE1vZHVsZSAhPT0gdW5kZWZpbmVkKSB7XG5cdFx0cmV0dXJuIGNhY2hlZE1vZHVsZS5leHBvcnRzO1xuXHR9XG5cdC8vIENyZWF0ZSBhIG5ldyBtb2R1bGUgKGFuZCBwdXQgaXQgaW50byB0aGUgY2FjaGUpXG5cdHZhciBtb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdID0ge1xuXHRcdC8vIG5vIG1vZHVsZS5pZCBuZWVkZWRcblx0XHQvLyBubyBtb2R1bGUubG9hZGVkIG5lZWRlZFxuXHRcdGV4cG9ydHM6IHt9XG5cdH07XG5cblx0Ly8gRXhlY3V0ZSB0aGUgbW9kdWxlIGZ1bmN0aW9uXG5cdF9fd2VicGFja19tb2R1bGVzX19bbW9kdWxlSWRdKG1vZHVsZSwgbW9kdWxlLmV4cG9ydHMsIF9fd2VicGFja19yZXF1aXJlX18pO1xuXG5cdC8vIFJldHVybiB0aGUgZXhwb3J0cyBvZiB0aGUgbW9kdWxlXG5cdHJldHVybiBtb2R1bGUuZXhwb3J0cztcbn1cblxuIiwiLy8gZGVmaW5lIGdldHRlciBmdW5jdGlvbnMgZm9yIGhhcm1vbnkgZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5kID0gZnVuY3Rpb24oZXhwb3J0cywgZGVmaW5pdGlvbikge1xuXHRmb3IodmFyIGtleSBpbiBkZWZpbml0aW9uKSB7XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGRlZmluaXRpb24sIGtleSkgJiYgIV9fd2VicGFja19yZXF1aXJlX18ubyhleHBvcnRzLCBrZXkpKSB7XG5cdFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywga2V5LCB7IGVudW1lcmFibGU6IHRydWUsIGdldDogZGVmaW5pdGlvbltrZXldIH0pO1xuXHRcdH1cblx0fVxufTsiLCJfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSBmdW5jdGlvbihvYmosIHByb3ApIHsgcmV0dXJuIE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChvYmosIHByb3ApOyB9IiwiLy8gZGVmaW5lIF9fZXNNb2R1bGUgb24gZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5yID0gZnVuY3Rpb24oZXhwb3J0cykge1xuXHRpZih0eXBlb2YgU3ltYm9sICE9PSAndW5kZWZpbmVkJyAmJiBTeW1ib2wudG9TdHJpbmdUYWcpIHtcblx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgU3ltYm9sLnRvU3RyaW5nVGFnLCB7IHZhbHVlOiAnTW9kdWxlJyB9KTtcblx0fVxuXHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgJ19fZXNNb2R1bGUnLCB7IHZhbHVlOiB0cnVlIH0pO1xufTsiLCJpbXBvcnQgeyBicm93c2VyU3VwcG9ydHNXZWJBdXRobiwgc3RhcnRBdXRoZW50aWNhdGlvbiB9IGZyb20gJ0BzaW1wbGV3ZWJhdXRobi9icm93c2VyJztcblxuY29uc3QgaW5pdGlhbGl6ZWQgPSBuZXcgV2Vha01hcCgpO1xuXG5jb25zdCBpbml0ID0gKGVsZW1lbnQpID0+IHtcbiAgICBpZiAoaW5pdGlhbGl6ZWQuaGFzKGVsZW1lbnQpKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG5cbiAgICBpbml0aWFsaXplZC5zZXQoZWxlbWVudCwgdHJ1ZSk7XG5cbiAgICBjb25zdCBidXR0b24gPSBlbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLXBhc3NrZXktYnV0dG9uXScpO1xuICAgIGNvbnN0IGVsZW1FcnJvciA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLXBhc3NrZXktZXJyb3JdJyk7XG5cbiAgICBpZiAoIWJ1dHRvbiB8fCAhZWxlbUVycm9yIHx8ICFlbGVtZW50LmRhdGFzZXQucGFzc2tleUNvbmZpZykge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuXG4gICAgY29uc3QgY29uZmlnID0gSlNPTi5wYXJzZShlbGVtZW50LmRhdGFzZXQucGFzc2tleUNvbmZpZyk7XG5cbiAgICBidXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBhc3luYyAoKSA9PiB7XG4gICAgICAgIGVsZW1FcnJvci5pbm5lckhUTUwgPSAnJztcblxuICAgICAgICBpZiAoIWJyb3dzZXJTdXBwb3J0c1dlYkF1dGhuKCkpIHtcbiAgICAgICAgICAgIGVsZW1FcnJvci5pbm5lckhUTUwgPSBjb25maWcudW5zdXBwb3J0ZWQ7XG5cbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IHJlc3AgPSBhd2FpdCBmZXRjaChjb25maWcub3B0aW9uc1VybCwge1xuICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICBoZWFkZXJzOiB7ICdDb250ZW50LVR5cGUnOiAnYXBwbGljYXRpb24vanNvbicgfSxcbiAgICAgICAgICAgIGJvZHk6IEpTT04uc3RyaW5naWZ5KHt9KSxcbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy8gTWFrZSBzdXJlIHdlIGFsd2F5cyBoYXZlIHRoZSBjb3JyZWN0IHJlcXVlc3QgdG9rZW4gYW5kIGNvb2tpZVxuICAgICAgICBjb25zdCBzY3JpcHQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzY3JpcHQnKTtcbiAgICAgICAgc2NyaXB0LnNyYyA9IGNvbmZpZy5yZXF1ZXN0VG9rZW5TY3JpcHQ7XG4gICAgICAgIHNjcmlwdC5hc3luYyA9IHRydWU7XG4gICAgICAgIGRvY3VtZW50LmJvZHkuYXBwZW5kKHNjcmlwdCk7XG5cbiAgICAgICAgY29uc3Qgb3B0aW9uc0pTT04gPSBhd2FpdCByZXNwLmpzb24oKTtcblxuICAgICAgICBpZiAoJ2Vycm9yJyA9PT0gb3B0aW9uc0pTT04uc3RhdHVzKSB7XG4gICAgICAgICAgICBlbGVtRXJyb3IuaW5uZXJUZXh0ID0gY29uZmlnLmFzc2VydGlvbkZhaWx1cmU7XG5cbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGxldCBhdHRSZXNwO1xuXG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICBhdHRSZXNwID0gYXdhaXQgc3RhcnRBdXRoZW50aWNhdGlvbih7IG9wdGlvbnNKU09OIH0pO1xuICAgICAgICB9IGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgZWxlbUVycm9yLmlubmVyVGV4dCA9IGNvbmZpZy5hc3NlcnRpb25GYWlsdXJlO1xuXG4gICAgICAgICAgICB0aHJvdyBlcnJvcjtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IHZlcmlmaWNhdGlvblJlc3AgPSBhd2FpdCBmZXRjaChjb25maWcucmVzdWx0VXJsLCB7XG4gICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgIGhlYWRlcnM6IHsgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi9qc29uJyB9LFxuICAgICAgICAgICAgYm9keTogSlNPTi5zdHJpbmdpZnkoYXR0UmVzcCksXG4gICAgICAgIH0pO1xuXG4gICAgICAgIGNvbnN0IHZlcmlmaWNhdGlvbkpTT04gPSBhd2FpdCB2ZXJpZmljYXRpb25SZXNwLmpzb24oKTtcblxuICAgICAgICBpZiAoJ2Vycm9yJyA9PT0gdmVyaWZpY2F0aW9uSlNPTi5zdGF0dXMpIHtcbiAgICAgICAgICAgIGVsZW1FcnJvci5pbm5lclRleHQgPSBjb25maWcuYXNzZXJ0aW9uRmFpbHVyZTtcblxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgd2luZG93LmxvY2F0aW9uID0gY29uZmlnLnJlZGlyZWN0IHx8IHdpbmRvdy5sb2NhdGlvbi5ocmVmO1xuICAgIH0pO1xufTtcblxuY29uc3Qgc2VsZWN0b3IgPSAnW2RhdGEtcGFzc2tleS1sb2dpbl0nO1xuXG5uZXcgTXV0YXRpb25PYnNlcnZlcigobXV0YXRpb25zTGlzdCkgPT4ge1xuICAgIGZvciAoY29uc3QgbXV0YXRpb24gb2YgbXV0YXRpb25zTGlzdCkge1xuICAgICAgICBpZiAobXV0YXRpb24udHlwZSA9PT0gJ2NoaWxkTGlzdCcpIHtcbiAgICAgICAgICAgIGZvciAoY29uc3Qgbm9kZSBvZiBtdXRhdGlvbi5hZGRlZE5vZGVzKSB7XG4gICAgICAgICAgICAgICAgaWYgKG5vZGUubWF0Y2hlcz8uKHNlbGVjdG9yKSkge1xuICAgICAgICAgICAgICAgICAgICBpbml0KG5vZGUpO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGlmIChub2RlLnF1ZXJ5U2VsZWN0b3JBbGwpIHtcbiAgICAgICAgICAgICAgICAgICAgZm9yIChjb25zdCBlbGVtZW50IG9mIG5vZGUucXVlcnlTZWxlY3RvckFsbChzZWxlY3RvcikpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGluaXQoZWxlbWVudCk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG59KS5vYnNlcnZlKGRvY3VtZW50LCB7XG4gICAgYXR0cmlidXRlczogZmFsc2UsXG4gICAgY2hpbGRMaXN0OiB0cnVlLFxuICAgIHN1YnRyZWU6IHRydWUsXG59KTtcblxuZm9yIChjb25zdCBlbGVtZW50IG9mIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoc2VsZWN0b3IpKSB7XG4gICAgaW5pdChlbGVtZW50KTtcbn1cbiJdLCJuYW1lcyI6WyJicm93c2VyU3VwcG9ydHNXZWJBdXRobiIsInN0YXJ0QXV0aGVudGljYXRpb24iLCJpbml0aWFsaXplZCIsIldlYWtNYXAiLCJpbml0IiwiZWxlbWVudCIsImhhcyIsInNldCIsImJ1dHRvbiIsInF1ZXJ5U2VsZWN0b3IiLCJlbGVtRXJyb3IiLCJkb2N1bWVudCIsImRhdGFzZXQiLCJwYXNza2V5Q29uZmlnIiwiY29uZmlnIiwiSlNPTiIsInBhcnNlIiwiYWRkRXZlbnRMaXN0ZW5lciIsImlubmVySFRNTCIsInVuc3VwcG9ydGVkIiwicmVzcCIsImZldGNoIiwib3B0aW9uc1VybCIsIm1ldGhvZCIsImhlYWRlcnMiLCJib2R5Iiwic3RyaW5naWZ5Iiwic2NyaXB0IiwiY3JlYXRlRWxlbWVudCIsInNyYyIsInJlcXVlc3RUb2tlblNjcmlwdCIsImFzeW5jIiwiYXBwZW5kIiwib3B0aW9uc0pTT04iLCJqc29uIiwic3RhdHVzIiwiaW5uZXJUZXh0IiwiYXNzZXJ0aW9uRmFpbHVyZSIsImF0dFJlc3AiLCJlcnJvciIsInZlcmlmaWNhdGlvblJlc3AiLCJyZXN1bHRVcmwiLCJ2ZXJpZmljYXRpb25KU09OIiwid2luZG93IiwibG9jYXRpb24iLCJyZWRpcmVjdCIsImhyZWYiLCJzZWxlY3RvciIsIk11dGF0aW9uT2JzZXJ2ZXIiLCJtdXRhdGlvbnNMaXN0IiwibXV0YXRpb24iLCJ0eXBlIiwibm9kZSIsImFkZGVkTm9kZXMiLCJtYXRjaGVzIiwicXVlcnlTZWxlY3RvckFsbCIsIm9ic2VydmUiLCJhdHRyaWJ1dGVzIiwiY2hpbGRMaXN0Iiwic3VidHJlZSJdLCJzb3VyY2VSb290IjoiIn0=