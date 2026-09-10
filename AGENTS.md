# Repository guidelines

Guidance for coding agents working in the Contao CMS monorepo. Keep changes focused, preserve backwards compatibility,
and follow the conventions of the area being edited. Do not over-engineer!

## Repository context

- This repository is the development monorepo for Contao 6.0. It is split into the individual Composer packages listed
  in `monorepo.yml`; the monorepo package itself is not intended for production installations.
- Each top-level `*-bundle` directory is an individual Symfony bundle and Composer package. `test-case` contains shared
  testing infrastructure and `vendor-bin` contains isolated development tools.
- New PHP code normally lives in a bundle's `src/` directory and uses its PSR-4 namespace. Tests live in the
  corresponding bundle's `tests/` directory.
- Asset source files live in `core-bundle/assets/`.
- Do not manually edit generated or third-party files in `node_modules/` or `vendor/`.

## New code and legacy code

Contao deliberately contains two kinds of PHP code with different conventions. Determine which kind a file belongs to
before editing or formatting it.

### New code

- New PHP code lives primarily in each bundle's `src/` directory, with tests in `tests/`. It uses modern Contao and
  Symfony patterns, PSR-4 autoloading, `declare(strict_types=1)`, spaces for indentation, and the conventions enforced
  by the root `ecs.php`.
- Prefer dependency injection, typed properties and return types, attributes, events, and existing service
  abstractions. Follow nearby code when choosing between equivalent patterns.
- Rector and PHPStan intentionally cover only new code. Do not extend either tool to legacy code unless explicitly
  requested.

### Legacy code

- Executable legacy code is located inside each bundle's `contao/*` tree, except for these resource/configuration
  directories:
  - `contao/config/`: Contao configuration
  - `contao/dca/`: Data Container Array (DCA) definitions
  - `contao/languages/`: translations
  - `contao/templates/`: Twig templates
- Legacy PHP commonly uses tabs, long array syntax, braces on the next line, and intentionally differs from the coding
  style used under `src/`.
- Never apply the root `ecs.php`, Rector, PHPStan, or modern style conventions wholesale to legacy code. Avoid unrelated
  modernization while fixing legacy behavior.
- Legacy coding style is defined separately in `vendor-bin/ecs/config/legacy.php`. Its configured scope also includes
  PHP configuration and DCA files below `contao/`, while excluding resources such as translations and templates.
- `.editorconfig` is authoritative for indentation. In particular, files below `*/contao/` generally use tabs, whereas
  modern PHP and Twig files use four spaces.

## Working principles

- Inspect the bundle's `composer.json`, neighboring implementations, and existing tests before changing behavior or
  public APIs.
- For a bug fix, first add or adjust a focused regression test when the behavior can reasonably be tested. Then
  implement the smallest fix and demonstrate that the test passes.
- Preserve backwards compatibility unless a breaking change is explicitly requested. Treat public classes, service
  IDs, configuration keys, templates, hooks, events, and serialized/database data as compatibility-sensitive.
- Use Doctrine DBAL parameter binding for dynamic values and existing Contao filesystem, routing, security,
  translation, and escaping abstractions instead of duplicating them.
- Add comments only when they explain intent or a non-obvious constraint.
- Do not edit changelogs, create commits, or change generated dependency files unless the task explicitly requires it.
- Never discard unrelated working-tree changes. Keep patches narrowly scoped.

## Tests

- Add tests to the bundle that owns the behavior and mirror the source namespace/directory where practical.
- Standalone bundle tests exist only for bundles that contain a `tests/` directory.
- Run the narrowest useful test first, then broaden verification according to the risk and reach of the change.
- Unit tests do not require a database.
- Functional tests require a configured `contao_test` database as described in `README.md`.

Useful commands from the repository root:

```bash
# All unit tests
composer unit-tests

# A focused test class or method
composer unit-tests -- --filter TestName

# Functional tests (requires the test database)
composer functional-tests

# Static analysis for new code
composer phpstan

# Coding style for new code
composer ecs-default -- --no-progress-bar

# Coding style for legacy code
composer ecs-legacy -- --no-progress-bar

# Check Rector changes without modifying files
vendor-bin/rector/vendor/bin/rector --dry-run --no-progress-bar

# Other repository checks
composer twig-cs-fixer
composer service-linter
composer monorepo-tools
composer depcheck

# Assets checks and production build
npx biome ci core-bundle/assets
npx stylelint core-bundle/assets/styles core-bundle/contao/themes/flexible/styles
npm run build
```

The Composer ECS and Twig formatting scripts fix files in place. Inspect their diff afterward and do not retain
unrelated formatting changes. The aggregate `composer all` command also runs modifying tools, so use it only when a
full repository-wide pass is intended.

## Verification and handoff

- Report exactly which checks were run and whether they passed. If a check cannot run because a database, network
  connection, extension, or development tool is unavailable, state that limitation rather than treating it as a code
  failure.
- Before finishing, inspect the complete diff for accidental style conversion, especially when a change crosses
  between `src/` and `contao/`.
- User-facing behavior changes should include an appropriate test and, when requested by the maintainer, corresponding
  upgrade, deprecation, or changelog documentation.
