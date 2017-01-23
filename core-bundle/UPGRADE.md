API changes
===========

Version 4.* to 4.3
------------------

### Form template

The form template `form.html5` has been renamed to `form_wrapper.html5`, so it
can be overridden with a custom template in the form settings.


Version 3.* to 4.0
------------------

### StringUtil class

Since the `String` class is not compatible with PHP 7, we have renamed it to
`StringUtil`. The `String` class remains available for reasons of backwards
compatibility, however it has been deprecated and will be removed in a future
version.


### Mime icons

The mime icons have been removed from all front end templates. Instead, a new
style sheet called `icons.css` has been added to the layout builder, which
restores the mime icons for downloads and enclosures via CSS.


### article_raster_designer hook

The "article_raster_designer" hook has been removed. Use the "getArticles" hook
instead and return a string to override the default articles content.


### Add submit button

The "add submit button" option in the form generator has been removed. To
generate an inline form, add a text field and a submit button and assign the
CSS class `inline-form` to the form element (requires the `form.css` style
sheet to be enabled in the page layout).


### Space before/after

The field "space before/after" has been removed. Use a CSS class instead and
define the spacing in your style sheet.


### CSS classes of included elements

If an element is included in another element, the CSS classes are now merged
instead of overwritten, e.g. if content element A has the CSS class `elemA` and
includes a front end module with the CSS class `elemB`, both CSS classes will
be applied (`class="elemA elemB"`).

Here's how to select the elements separately:

```css
.elemA {
    /* Content element only */
}

.elemB {
    /* Content element and front end module */
}

.elemB:not(.elemA) {
    /* Front end module only */
}
```


### Form option "tableless"

The form option "tableless" has been removed, because all forms are now
tableless by default. Instead, the `form.css` style sheet of the layout builder
has been enhanced to provide basic formattings for labels and input fields.

By default, labels and input fields are listed underneath each other. However,
if you add the CSS class `horizontal-form` to a form, they will be aligned in a
horizontal layout, similar to the old table-based layout.

If you add the CSS class `inline-form`, the widgets will be aligned vertically.


### Form field "headline"

The form field "headline" has been removed in favor of the "explanation" field.


### FORM_SUBMIT

Every form now appends its numeric ID to the `FORM_SUBMIT` parameter, so custom
forms used for triggering modules such as the login module have to be adjusted
to pass the correct form ID (e.g. `tl_login_12` instead of `tl_login`).


### Store form data

If a front end form is set up to store the submitted data in the database, date
and time fields are now automatically converted to Unix timestamps.


### Meta keywords

The meta keywords tag has been removed from the `fe_page.html5` template, as
it does not serve a purpose anymore. If you still want to use it, adjust the
template as follows:

```php
<?php $this->extend('fe_page'); ?>

<?php $this->block('meta'): ?>
  <?php $this->parent(); ?>
  <meta name="keywords" content="<?= $this->keywords ?>">
<?php $this->endblock(); ?>
```


### Template changes

Adding the schema.org tags required to insert an additional `<span>` element
into the following templates:

`cal_default.html5`

```php
<!-- OLD -->
<div class="event">
  <a href="<?= $event['href'] ?>"><?= $event['link'] ?></a>
</div>

<!-- NEW -->
<div class="event" itemscope itemtype="http://schema.org/Event">
  <a href="<?= $event['href'] ?>" itemprop="url"><span itemprop="name"><?= $event['link'] ?></span></a>
</div>
```

`mod_breadcrumb.html5`

```php
<!-- OLD -->
<li>
  <a href="<?= $item['href'] ?>"><?= $item['link'] ?></a>
</li>

<!-- NEW -->
<li itemscope itemtype="http://schema.org/ListItem" itemprop="itemListElement">
  <a href="<?= $item['href'] ?>" itemprop="url"><span itemprop="name"><?= $item['link'] ?></span></a>
</li>
```

`nav_default.html5`

```php
<!-- OLD -->
<li>
  <a href="<?= $item['href'] ?>"><?= $item['link'] ?></a>
</li>

<!-- NEW -->
<li>
  <a href="<?= $item['href'] ?>" itemprop="url"><span itemprop="name"><?= $item['link'] ?></span></a>
</li>
```


### Template name changes

The following templates have been renamed to match the content element or
module key:

 * `ce_accordion`       -> `ce_accordionSingle`
 * `ce_accordion_start` -> `ce_accordionStart`
 * `ce_accordion_stop`  -> `ce_accordionStop`
 * `ce_slider_start`    -> `ce_sliderStart`
 * `ce_slider_stop`     -> `ce_sliderStop`
 * `mod_article_list`   -> `mod_articlelist`
 * `mod_article_nav`    -> `mod_articlenav`
 * `mod_random_image`   -> `mod_randomImage`

The following templates have been consolidated:

 * `ce_hyperlink_image`  -> `ce_hyperlink`
 * `mod_article_plain`   -> `mod_article`
 * `mod_article_teaser`  -> `mod_article`
 * `mod_login_1cl`       -> `mod_login`
 * `mod_login_2cl`       -> `mod_login`
 * `mod_logout_1cl`      -> `mod_login`
 * `mod_logout_2cl`      -> `mod_login`
 * `mod_search_advanced` -> `mod_search`
 * `mod_search_simple`   -> `mod_search`

Generally, we now require the template names to match the content element or
module keys, so if your module has the key `taskList`, the corresponding
template should be named `mod_taskList.html5`.

Users can then create custom templates à la `mod_taskList_custom.html`, which
will be shown in the "custom module template" list.


### Front end module keys

The keys of the following front end modules have been changed:

 * `articleList` -> `articlelist`
 * `rss_reader`  -> `rssReader`


### Custom database drivers

The database classes have been mapped to the Doctrine DBAL, therefore custom
drivers are no longer supported. If you have been using a custom driver for a
database other than MySQL, use the corresponding Doctrine driver instead.


### dump()

The `dump()` function has been replaced by the Symfony debug bundle. Its output
will be added to the web profiler.


### tinymce.css and tiny_templates

The style sheet `files/tinymce.css` and the folder `files/tiny_templates` have
been removed. If you want to use the feature, please adjust the TinyMCE config
file, which is now a template (e.g. `be_tinyMCE.html5`).


### Frontend::parseMetaFile()

The `Frontend::parseMetaFile()` method was deprecated since Contao 3 and has
been removed in Contao 4.0.


### $_SESSION['TL_USER_LOGGED_IN']

The `$_SESSION['TL_USER_LOGGED_IN']` flag has been removed.


### PHP entry points

Contao 4 only uses a single PHP entry point, namely the `app.php` or
`app_dev.php` file. The previous PHP entry points have been removed and a route
has been set up for each one instead.

 - `contao/confirm.php`  -> `contao_backend_confirm`
 - `contao/file.php`     -> `contao_backend_file`
 - `contao/help.php`     -> `contao_backend_help`
 - `contao/index.php`    -> `contao_backend_login`
 - `contao/main.php`     -> `contao_backend`
 - `contao/page.php`     -> `contao_backend_page`
 - `contao/password.php` -> `contao_backend_password`
 - `contao/popup.php`    -> `contao_backend_popup`
 - `contao/preview.php`  -> `contao_backend_preview`
 - `contao/switch.php`   -> `contao_backend_switch`

The old paths are replaced automatically in the back end, still you should
adjust your templates to use `$this->route()` instead:

```php
// Old
<form action="contao/main.php">

// New
<form action="<?= $this->route('contao_backend') ?>">
```


### Disable aliases

In Contao 3, it was possible to disable aliases and make Contao use numeric IDs
only. This was a workaround for an old IIS server, which has now been dropped.

More information: https://github.com/contao/core-bundle/issues/118


### system/runonce.php

The `system/runonce.php` file is no longer supported. If you need to set up a
`runonce.php` file, put it in the `src/Resources/contao/config/` directory.


### DcaExtractor

The `DcaExtractor` class is no longer instantiable via `new DcaExtractor()`.
Use the `DcaExtractor::getInstance($table)` method instead.


### MooTools slimbox

The MooTools "slimbox" plugin has been removed. Use the MooTools "mediabox" or
the jQuery "colorbox" plugin instead.


### Message::generate()

The `Message` class now supports scopes, which can optionally be passed as
second argument:

```php
// Add an error message to "my-scope"
Message::addError('An error ocurred', 'my-scope');

// Generate all messages in "my-scope"
Message::generate('my-scope');
```

The scope defaults to `TL_MODE`. The previous arguments of the `generate()`
method have been removed. If you want to output the messages without the
wrapping element, use `Message::generateUnwrapped()` instead.


### prepareFormData hook

The "prepareFormData" hook now passes `$this` as last argument, just like in
any other hook.


### Markup changes

 * The navigation menus are now using `<strong>` instead of `<span>` to
   highlight the active menu item.

 * The search module is now using `<mark>` instead of `<span>` to highlight
   the keywords.

 * The newsletter channel menu is now using `<fieldset>` and `<legend>`
   instead of `<label>` and `<div>`.

 * The main section of the `fe_page.html` template now uses the `<main>` tag.

 * Submit buttons now use `<button type="submit">` instead of `<input>`.


### CSS class changes

 * The book navigation module now uses the CSS class `previous` instead of
   `prev` for the link to the previous page.

 * The pagination menu now uses the CSS class `active` instead of `current` for
   the active menu item.

 * The classes `odd` and `even` are now correctly assigned to tables.

 * The form submit widget now uses the CSS class `widget widget-submit` instead
   of `submit_container`.

 * The content syndication links of the `mod_article.html5` template now have
   CSS classes and the class "pdf_link" has been replaced with "syndication":

```html
<div class="syndication">
  <a href="..." class="print"></a>
  <a href="..." class="pdf"></a>
  <a href="..." class="facebook"></a>
  <a href="..." class="twitter"></a>
  <a href="..." class="gplus"></a>
</div>
```


### new File()

In Contao 3, `new File('tmp.txt')` automatically created the file if it did not
exist and all write operations such as `$file->write()` or `$file->append()`
were carried out directly on the target file.

This behavior could already be changed in Contao 3 by passing `true` as second
argument; the file was then only created if there was a write operation at all
and any operation was carried out on a temporary file first, which was then
moved to its final destination.

In Contao 4, this changed behavior has become the default and the second
argument has been dropped.


### Protected folders

In Contao 3, the user files in the `files/` directory were publicly available
via HTTP by default and it was possible to protect certain subfolders. Now, due
to a technical change, the user files are protected by default and subfolders
have to be published explicitly to be available via HTTP.
