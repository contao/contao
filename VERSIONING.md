# Semantic versioning

Starting with Contao 4.0, we are using [semantic versioning][1].

## What does this mean?

 * Bugfix releases must only contain backwards compatible bug fixes.
 * Minor releases can contain new backwards compatible features.
 * Any incompatible API change must be released as a new major version.

## API changes

Not everything that is backwards incompatible is also an API change! The API includes the public and the protected
methods of the Contao PHP classes, unless they are declared as `@internal`.

Template files are explicitely not part of the API and thus can be changed in minor and bugfix releases, even if the
change might break a customized version of the template or require adjusting CSS code to the new markup.

## Ramifications

Using semantic versioning also means that new major versions might be released more often than in the past.

[1]: https://semver.org
