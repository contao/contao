# API changes

## Version 5.* to 6.0

### Input encoding

User input is no longer filtered and encoded in Contao 6, which means that you have to ensure that all output is
properly encoded! The easiest way to do this is to use Twig templates.

If you send content to the browser without using Twig templates, make sure to use `StringUtil::specialchars()` or
the `contao.html_sanitizer` service to encode the output.

### HTML5 templates

Contao 6 no longer supports `.html5` templates. Use Twig templates instead.
