const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { test } = require('node:test');
const vm = require('node:vm');

function createObserver(bundle) {
    const source = readFileSync(require.resolve(`@hotwired/turbo/dist/${bundle}`), 'utf8');
    const start = source.indexOf('class LinkPrefetchObserver {');
    const end = source.indexOf('const unfetchableLink =', start);
    assert.ok(start >= 0 && end > start, 'The installed bundle must contain LinkPrefetchObserver');

    const requests = [];
    const listeners = new Map();
    const cache = new Map();
    const cacheKey = (url) => url.href.split('#')[0];
    let scheduled;
    const prefetchCache = {
        putLater(url, request) {
            scheduled = { url, request };
        },
        get(url) {
            return cache.get(cacheKey(url));
        },
        clear() {
            cache.clear();
            scheduled = undefined;
        },
    };
    const eventTarget = {
        readyState: 'complete',
        addEventListener(type, callback) {
            listeners.set(type, callback);
        },
        removeEventListener(type) {
            listeners.delete(type);
        },
    };
    class FetchRequest {
        constructor(delegate, method, location, body, target) {
            this.delegate = delegate;
            this.url = new URL(location.href);
            this.target = target;
            this.fetchOptions = { method: method.toUpperCase() };
            this.controller = new AbortController();
            this.abortSignal = this.controller.signal;
            requests.push(this);
        }

        cancel() {
            this.controller.abort();
        }
    }
    const Observer = vm.runInNewContext(`${source.slice(start, end)}; LinkPrefetchObserver`, {
        FetchRequest,
        FetchMethod: { get: 'get' },
        URLSearchParams,
        prefetchCache,
        cacheTtl: 10000,
        getMetaContent: () => null,
        getLocationForLink: (link) => new URL(link.href),
        unfetchableLink: () => false,
        linkToTheSamePage: () => false,
        linkOptsOut: () => false,
        nonSafeLink: () => false,
        eventPrevented: () => false,
    });
    const observer = new Observer({ canPrefetchRequestToLocation: () => true }, eventTarget);
    observer.start();

    return {
        observer,
        requests,
        emit(type, target, detail = {}) {
            listeners.get(type)({ target, detail });
            return detail;
        },
        flushPrefetch() {
            assert.ok(scheduled, 'A hover must schedule a prefetch');
            const { url, request } = scheduled;
            scheduled = undefined;
            cache.set(cacheKey(url), request);
            return request;
        },
    };
}

function link(name) {
    return {
        href: `https://example.test/${name}`,
        tagName: 'A',
        matches: () => true,
        getAttribute(name) {
            return name === 'href' ? this.href : null;
        },
    };
}

function usePrefetch(fixture, target, method = 'GET') {
    return fixture.emit('turbo:before-fetch-request', target, {
        url: new URL(target.href),
        fetchOptions: { method },
    });
}

for (const bundle of ['turbo.es2017-esm.js', 'turbo.es2017-umd.js']) {
    test(`${bundle}: re-hovering cannot abort a request adopted by a visit`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow');
        fixture.emit('mouseenter', target);
        const adopted = fixture.flushPrefetch();
        fixture.emit('turbo:before-visit', target, { url: target.href });
        assert.equal(usePrefetch(fixture, target).fetchRequest, adopted);

        fixture.emit('mouseleave', target);
        fixture.emit('mouseenter', target);

        assert.equal(adopted.abortSignal.aborted, false);
        assert.equal(fixture.requests.length, 2);
    });

    test(`${bundle}: later visits do not cancel an adopted prefetch`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow');
        fixture.emit('mouseenter', target);
        const adopted = fixture.flushPrefetch();
        assert.equal(usePrefetch(fixture, target).fetchRequest, adopted);

        fixture.emit('turbo:before-visit', link('other'), { url: link('other').href });

        assert.equal(adopted.abortSignal.aborted, false);
    });

    test(`${bundle}: an aborted cache entry is not adopted`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow');
        fixture.emit('mouseenter', target);
        const aborted = fixture.flushPrefetch();
        aborted.cancel();

        assert.equal(usePrefetch(fixture, target).fetchRequest, undefined);
    });

    test(`${bundle}: a new hover still cancels an unadopted request for the same URL`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow');
        fixture.emit('mouseenter', target);
        const obsolete = fixture.flushPrefetch();
        fixture.emit('mouseleave', target);
        fixture.emit('mouseenter', target);

        assert.equal(obsolete.abortSignal.aborted, true);
        assert.equal(fixture.requests[1].abortSignal.aborted, false);
    });

    test(`${bundle}: visiting another URL still cancels pending prefetches`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow');
        fixture.emit('mouseenter', target);
        const obsolete = fixture.flushPrefetch();
        fixture.emit('turbo:before-visit', link('other'), { url: link('other').href });

        assert.equal(obsolete.abortSignal.aborted, true);
    });

    test(`${bundle}: finishing an old request cannot untrack its replacement`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow');
        fixture.emit('mouseenter', target);
        const old = fixture.flushPrefetch();
        fixture.emit('mouseleave', target);
        fixture.emit('mouseenter', target);
        const replacement = fixture.flushPrefetch();
        fixture.observer.requestFinished(old);
        fixture.emit('mouseleave', target);
        fixture.emit('mouseenter', target);

        assert.equal(replacement.abortSignal.aborted, true);
        assert.equal(fixture.requests[2].abortSignal.aborted, false);
    });

    test(`${bundle}: fragment aliases release the same adopted request`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow#first');
        fixture.emit('mouseenter', target);
        const adopted = fixture.flushPrefetch();
        assert.equal(usePrefetch(fixture, link('slow#second')).fetchRequest, adopted);

        fixture.emit('mouseleave', target);
        fixture.emit('mouseenter', target);

        assert.equal(adopted.abortSignal.aborted, false);
    });

    test(`${bundle}: a rewritten request URL cannot leave an adopted request tracked`, () => {
        const fixture = createObserver(bundle);
        const target = link('slow');
        fixture.emit('mouseenter', target);
        const adopted = fixture.flushPrefetch();
        adopted.url = new URL('https://example.test/rewritten');
        assert.equal(usePrefetch(fixture, target).fetchRequest, adopted);

        fixture.emit('mouseleave', target);
        fixture.emit('mouseenter', target);
        const replacement = fixture.flushPrefetch();
        fixture.observer.requestFinished(adopted);
        fixture.emit('turbo:before-visit', link('other'), { url: link('other').href });

        assert.equal(adopted.abortSignal.aborted, false);
        assert.equal(replacement.abortSignal.aborted, true);
    });

    test(`${bundle}: form and non-GET requests leave cached prefetches untouched`, () => {
        for (const [tagName, method] of [['FORM', 'GET'], ['A', 'POST']]) {
            const fixture = createObserver(bundle);
            const target = link('slow');
            fixture.emit('mouseenter', target);
            const pending = fixture.flushPrefetch();
            const requestTarget = { ...target, tagName };

            assert.equal(usePrefetch(fixture, requestTarget, method).fetchRequest, undefined);
            assert.equal(pending.abortSignal.aborted, false);
            assert.equal(usePrefetch(fixture, target).fetchRequest, pending);
        }
    });
}
