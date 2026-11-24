# Repository guidelines

Guidance for coding agents working in the Contao CMS monorepo. Keep changes focused, preserve backwards compatibility,
and follow the conventions of the area being edited. **Do not over-engineer!**

## Core Constraints (Strict)

- Preserve backwards compatibility (public classes, service IDs, templates, hooks, events, database schema) unless
  explicitly requested.
- Do not apply wholesale style conversions or unsolicited refactoring.
- Do not create Git commits, edit changelogs, or touch generated dependency files unless requested.
- Never discard unrelated working-tree changes. Keep patches narrowly scoped.

## Repository context

- This repository is the development monorepo for Contao. It is split into individual Composer packages listed in
  `monorepo.yml`. The root package itself is not intended for production.
- Top-level `*-bundle/` directories are individual Symfony bundles. `test-case/` contains shared testing
  infrastructure; `vendor-bin/` contains isolated dev tools.
- New PHP code lives in a bundle's `src/` directory (PSR-4). Tests live in the corresponding `tests/` directory.
- Standalone bundle tests exist only for bundles that contain a `tests/` directory.
- If a bundle provides asset source files, they live in its `assets/` directory.
- Do not manually edit generated or third-party files in `node_modules/` or `vendor/`.

## New code vs. Legacy code

Contao deliberately contains two kinds of PHP code with different conventions. **Determine the code type before editing
or formatting.**

### New code (`src/`)

- Uses modern Contao/Symfony patterns, PSR-4, `declare(strict_types=1)`, spaces for indentation, and rules from root
  `ecs.php`.
- Prefer dependency injection, typed properties/returns, attributes, events, and existing service abstractions.
- Rector and PHPStan intentionally cover **only** new code.

### Legacy code (`contao/`)

- Located inside each bundle's `contao/` directory.
- Legacy PHP uses tabs, long array syntax, and brace-on-next-line formatting.
- **Never** apply root `ecs.php`, Rector, PHPStan, or modern style conventions to legacy code.
- Legacy style is configured in `vendor-bin/ecs/config/legacy.php`.
- `.editorconfig` is authoritative: `*/contao/` uses tabs; `src/` and Twig use 4 spaces.

## Working principles & Workflow

1. **Context First:** Inspect the bundle's `composer.json`, neighboring implementations, and existing tests before
   changing behavior or public APIs.
2. **Bug Fixes:** For testable bug fixes, first add or adjust a focused regression test. Implement the smallest
   possible fix, then verify the test passes.
3. **Security & Abstractions:** Use Doctrine DBAL parameter binding for dynamic values. Use existing Contao
   abstractions for filesystem, routing, security, translation, and escaping.
4. **Comments:** Add comments only when explaining intent or non-obvious constraints.

## Verification & Commands

Run the narrowest useful check first, then broaden verification.

```bash
# Unit tests
vendor/bin/phpunit
vendor/bin/phpunit --filter TestName

# Functional tests (requires configured contao_test database)
vendor/bin/phpunit --testsuite=functional

# Static analysis & CS for new code
vendor-bin/phpstan/vendor/bin/phpstan analyze
vendor-bin/ecs/vendor/bin/ecs check

# CS for legacy code
vendor-bin/ecs/vendor/bin/ecs check --config=vendor-bin/ecs/config/legacy.php

# Rector dry-run
vendor-bin/rector/vendor/bin/rector --dry-run

# Additional linters & build
vendor-bin/twig-cs-fixer/vendor/bin/twig-cs-fixer check
vendor-bin/service-linter/bin/lint-service-ids
vendor-bin/monorepo-tools/vendor/bin/monorepo-tools composer-json --validate
vendor/bin/composer-dependency-analyser --config=depcheck.php

# Assets linter & build
npx biome ci core-bundle/assets
npx stylelint core-bundle/assets/styles
npx encore production
```

## Security reports

Before reporting any security finding (security audit, code review, or any other task), read the `SECURITY.md` in the
repository root. It lists known non-issues and the project's threat model.

- Do not report anything that matches a non-issue listed there.
- If a finding only partially matches a non-issue (different preconditions, different impact), report it and state
  explicitly how it differs.
- If the `SECURITY.md` is missing or unreadable, say so in your report instead of proceeding silently.
