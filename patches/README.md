# Turbo prefetch patch

Contao applies `@hotwired+turbo+8.0.23.patch` during `npm install`. The patch records pending prefetch requests so an obsolete hover request can be cancelled. It also transfers an adopted request out of prefetch tracking when navigation reuses it, and avoids reusing an aborted response.

Both the ESM and UMD bundles are patched. Keep their changes in sync.

Run the observer regressions after installing dependencies:

```bash
npm run test:turbo
```

The tests execute the observer from each installed bundle with controlled request, cache and event collaborators. They check adoption, aborted-cache fallback, pending-request cancellation and replacement identity. They do not replace browser navigation tests.

Upstream change: https://github.com/hotwired/turbo/pull/1490

Contao ownership regression: https://github.com/contao/contao/issues/10424
