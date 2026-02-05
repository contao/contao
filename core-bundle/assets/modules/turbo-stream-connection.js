export class TurboStreamConnection {
    _abortController = new AbortController();
    _abortSignal = { reason: 'The request was substituted.' };

    /**
     * Requests a stream response using GET and lets Turbo handle it.
     *
     * @param url The URL of the Symfony controller answering the stream request.
     * @param query_params An object of query parameters. If the value is an array, a key "foo" will be named "foo[]" and appear multiple times.
     * @param abortPending If set to true, previous requests that are still pending will be aborted.
     *
     * @returns {Promise<TurboStreamResult>}
     */
    async get(url, query_params = null, abortPending = false) {
        if (abortPending) {
            this.abortPending();
        }

        const params = {
            method: 'get',
            headers: {
                Accept: 'text/vnd.turbo-stream.html',
            },
            signal: this._abortController.signal,
        };

        let response;

        try {
            response = await fetch(this.constructor.buildURL(url, query_params), params);
        } catch (e) {
            if (e !== this._abortSignal) {
                if (window.console) {
                    console.error(`There was an error fetching the Turbo stream response from "${url}"`);
                }

                return new TurboStreamResult('error', response);
            }

            return new TurboStreamResult('aborted');
        }

        if (response.redirected) {
            document.location = response.url;

            return new TurboStreamResult('error', response);
        }

        if (!response.headers.get('content-type').startsWith('text/vnd.turbo-stream.html') || response.status >= 300) {
            if (window.console) {
                console.error(`The Turbo stream response from "${url}" has an unprocessable format.`);
            }

            return new TurboStreamResult('error', response);
        }

        const html = await response.text();
        Turbo.renderStreamMessage(html);

        return new TurboStreamResult('ok', response);
    }

    abortPending() {
        this._abortController?.abort(this._abortSignal);
        this._abortController = new AbortController();
    }

    static buildURL(url, query_params) {
        if (query_params === null) {
            return url;
        }

        const pairs = [];

        for (const [key, value] of Object.entries(query_params)) {
            if (!Array.isArray(value)) {
                pairs.push([key, value]);
                continue;
            }

            for (const value1 of value) {
                pairs.push([`${key}[]`, value1]);
            }
        }

        return `${url}?${new URLSearchParams(pairs).toString()}`;
    }
}

export class TurboStreamResult {
    constructor(resultState, response = null) {
        this.resultState = resultState;
        this.response = response;
    }

    get ok() {
        return this.resultState === 'ok';
    }

    get aborted() {
        return this.resultState === 'aborted';
    }

    get error() {
        return this.resultState === 'error';
    }
}
