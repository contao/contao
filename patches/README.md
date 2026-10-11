# Turbo prefetch patch

Contao applies `@hotwired+turbo+8.0.23.patch` during `npm install`. The patch records pending prefetch requests so an obsolete hover request can be cancelled. It also transfers an adopted request out of prefetch tracking when navigation reuses it, and avoids reusing an aborted response.

Both the ESM and UMD bundles are patched. Keep their changes in sync. Adoption and pending-request removal use private helpers shared with the upstream follow-up. Pending requests are removed by identity even if their URL changes.

The consuming `FetchRequest` retains the adopted request and forwards cancellation to it. This keeps a superseded navigation from rendering a delayed prefetched response, including cancellation after response headers while the body is still loading. No abort-forwarding event listener is installed.

Run the observer and consuming-request regressions after installing dependencies:

```bash
npm run test:turbo
```

The observer tests execute each installed bundle's observer with controlled request, cache and event collaborators. They check adoption, aborted-cache fallback, pending-request cancellation and replacement identity. The consuming-request tests execute the actual installed `FetchRequest` classes with controlled collaborators. They cover in-flight and already-cancelled adoption, late responses, pending body cancellation, unrelated requests and error propagation. They do not replace browser navigation tests.

Upstream change: https://github.com/hotwired/turbo/pull/1490

Contao ownership regression: https://github.com/contao/contao/issues/10424
