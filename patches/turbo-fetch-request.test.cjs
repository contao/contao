const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { test } = require('node:test');
const vm = require('node:vm');

function fixture(bundle) {
    const source = readFileSync(require.resolve(`@hotwired/turbo/dist/${bundle}`), 'utf8');
    const start = source.indexOf('class FetchRequest {');
    const end = source.indexOf('function isSafe(', start);
    assert.ok(start >= 0 && end > start, 'The installed bundle must contain FetchRequest');
    let adopted;
    const delivered = [];
    const finished = [];
    const errors = [];
    let started = () => {};
    const delegate = {
        prepareRequest() {},
        requestStarted(request) { started(request); },
        requestSucceededWithResponse(request, response) { delivered.push(response); },
        requestFailedWithResponse() {},
        requestPreventedHandlingResponse() {},
        requestErrored(request, error) { errors.push(error); },
        requestFinished(request) { finished.push(request); },
    };
    class FetchResponse {
        constructor(response) { this.response = response; }
        get succeeded() { return this.response.ok; }
        get responseHTML() { return this.response.clone().text(); }
    }
    const Request = vm.runInNewContext(`${source.slice(start, end)}; FetchRequest`, {
        AbortController, URLSearchParams, FormData,
        FetchResponse,
        FetchEnctype: { urlEncoded: 'application/x-www-form-urlencoded' },
        FetchMethod: { get: 'get' },
        expandURL: (url) => new URL(url),
        buildResourceAndBody: (url) => [url, null],
        isSafe: (method) => method.toLowerCase() === 'get',
        fetchWithTurboHeaders: () => Promise.resolve(new Response('Ordinary')),
        dispatch(name, options) {
            if (name === 'turbo:before-fetch-request' && adopted) options.detail.fetchRequest = adopted;
            return { detail: options.detail, defaultPrevented: false };
        },
    });
    adopted = new Request(delegate, 'get', new URL('https://example.test/slow'));
    const consumer = new Request(delegate, 'get', adopted.url);
    return {
        adopted, consumer, delivered, finished, errors,
        onStarted(callback) { started = callback; },
        ordinary() {
            adopted = null;
            return new Request(delegate, 'get', new URL('https://example.test/ordinary'));
        },
    };
}

function pending(fixture) {
    let resolve;
    fixture.adopted.response = new Promise(done => { resolve = done; });
    return resolve;
}

for (const bundle of ['turbo.es2017-esm.js', 'turbo.es2017-umd.js']) {
    test(`${bundle}: canceling a consumer cancels an adopted pending request`, async () => {
        const f = fixture(bundle);
        const resolve = pending(f);
        f.onStarted(() => f.consumer.cancel());
        const performed = f.consumer.perform();
        resolve(new Response('Stale'));
        await performed;
        assert.equal(f.adopted.abortSignal.aborted, true);
        assert.equal(f.delivered.length, 0);
        assert.deepEqual(f.finished, [f.consumer]);
        assert.deepEqual(f.errors, []);
    });

    test(`${bundle}: an already canceled consumer cancels its adopted request`, async () => {
        const f = fixture(bundle);
        f.adopted.response = Promise.resolve(new Response('Stale'));
        f.consumer.cancel();
        await f.consumer.perform();
        assert.equal(f.adopted.abortSignal.aborted, true);
        assert.equal(f.delivered.length, 0);
        assert.deepEqual(f.finished, [f.consumer]);
    });

    test(`${bundle}: late shared responses are not delivered when fetch ignores abort`, async () => {
        const f = fixture(bundle);
        const resolve = pending(f);
        let started;
        const ready = new Promise(done => { started = done; });
        f.onStarted(started);
        const performed = f.consumer.perform();
        await ready;
        f.consumer.cancel();
        resolve(new Response('Stale'));
        await performed;
        assert.equal(f.delivered.length, 0);
        assert.equal(f.adopted.abortSignal.aborted, true);
        assert.deepEqual(f.finished, [f.consumer]);
    });

    test(`${bundle}: cancellation after headers still aborts pending response bodies`, async () => {
        const f = fixture(bundle);
        const body = new ReadableStream({
            start(controller) {
                f.adopted.abortSignal.addEventListener('abort', () => {
                    controller.error(new DOMException('Aborted', 'AbortError'));
                }, { once: true });
            },
        });
        f.adopted.response = Promise.resolve(new Response(body));
        await f.consumer.perform();
        assert.equal(f.delivered.length, 1);
        const text = f.delivered[0].responseHTML.catch(error => error);
        f.consumer.cancel();
        assert.equal(f.adopted.abortSignal.aborted, true);
        assert.ok((await text) instanceof Error);
    });

    test(`${bundle}: unrelated cancellation leaves an adopted request alive`, async () => {
        const f = fixture(bundle);
        f.adopted.response = Promise.resolve(new Response('Current'));
        await f.consumer.perform();
        f.ordinary().cancel();
        assert.equal(f.adopted.abortSignal.aborted, false);
        assert.equal(f.delivered.length, 1);
    });

    test(`${bundle}: unrelated request errors remain reported and rethrown`, async () => {
        const f = fixture(bundle);
        const error = new TypeError('Network failed');
        f.adopted.response = Promise.reject(error);
        assert.equal(await f.consumer.perform().catch(caught => caught), error);
        assert.deepEqual(f.errors, [error]);
        assert.deepEqual(f.finished, [f.consumer]);
    });

    test(`${bundle}: ordinary request cancellation remains independent`, async () => {
        const f = fixture(bundle);
        const ordinary = f.ordinary();
        const response = await ordinary.perform();
        ordinary.cancel();
        assert.equal(ordinary.abortSignal.aborted, true);
        assert.equal(f.adopted.abortSignal.aborted, false);
        assert.equal(await response.responseHTML, 'Ordinary');
        assert.deepEqual(f.finished, [ordinary]);
    });
}
