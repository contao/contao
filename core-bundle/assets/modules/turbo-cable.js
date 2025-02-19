export class TurboCable {
    _abortController = null;
    _abortSignal = { reason: 'The request was substituted.' }

    /**
     * Requests a stream response and lets Turbo handle it.
     *
     * @param url The URL of the Symfony controller answering the stream request.
     * @param query_params An object of query parameters. If the value is an array, a key "foo" will be named "foo[]" and appear multiple times.
     * @param abortPending If set to true, previous requests that are still pending will be aborted.
     *
     * @returns {Promise<void>}
     */
    async getStream(url, query_params = null, abortPending = false) {
        let params = {
            method: 'get',
            headers: {
                'Accept': 'text/vnd.turbo-stream.html',
            }
        };

        if (abortPending) {
            if (null !== this._abortController) {
                this._abortController.abort(this._abortSignal);
            }

            this._abortController = new AbortController();

            params = {
                ...params,
                signal: this._abortController.signal,
            }
        }

        let response;

        try {
            response = await fetch(TurboCable.buildURL(url, query_params), params);
        } catch (e) {
            if(e !== this._abortSignal) {
                if (window.console) {
                    console.error(`There was an error fetching the Turbo stream response from "${url}"`);
                }
            }

            return;
        }

        if (response.redirected) {
            document.location = response.url;

            return;
        }

        if (!response.headers.get('content-type').startsWith('text/vnd.turbo-stream.html') || response.status >= 300) {
            if (window.console) {
                console.error(`The Turbo stream response from "${url}" has an unprocessable format.`);
            }

            return;
        }

        const html = await response.text()
        Turbo.renderStreamMessage(html);
    }

    static buildURL(url, query_params) {
        if (query_params === null) {
            return url;
        }

        let pairs = [];

        for (const [key, value] of Object.entries(query_params)) {
            if (!Array.isArray(value)) {
                pairs.push([key, value]);
                continue;
            }

            value.forEach(value => pairs.push([key + '[]', value]));
        }

        return url + '?' + new URLSearchParams(pairs).toString();
    }
}
