# Contributing

The Contao [release plan][1] follows a biannual model, with new feature releases (aka minor versions) being published
every February and August. This results in the following deadlines:

## Development

Pull requests that add new features must be complete and submitted by the **end of December** (February release) and the
**end of June** (August release). A pull request is considered "complete" if it meets the following requirements:

 * The PR must no longer be a draft.
 * All functions must be implemented.
 * All unit tests must be in place.
 * The CI checks must not fail.

Incomplete pull requests will be moved to the next milestone after the deadline.

## Review

Pull requests will be reviewed and merged until mid-January (February release) and mid-July (August release). During
this time, there will most likely be questions or requests for changes that need to be addressed by the creator of the
pull request. If there is no agreement by the end of the review phase, the pull request is moved to the next milestone.

## Release candidate

After the review phase, a feature-complete release candidate is published. From this point on, only pull requests that
fix bugs are accepted. Pull requests that add new features will automatically be assigned to the next milestone, unless
the new feature is required to fix a problem or replaces a faulty implementation.

## Release

Stable versions are released mid-February and mid-August.

[1]: https://contao.org/en/release-plan
