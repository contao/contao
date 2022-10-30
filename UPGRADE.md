# API changes

## Version 4.* to 5.0

### Contao 3 theme icons

The old `.gif` images that were used for the back end theme in Contao 3 have been removed. Use the `.svg` icons instead.

### TL_ASSETS_URL and TL_FILES_URL

The constants `TL_ASSETS_URL` and `TL_FILES_URL` have been removed. Use the assets or files context instead:

 ```php
$container = System::getContainer();
echo $container->get('contao.assets.assets_context')->getStaticUrl();
echo $container->get('contao.assets.files_context')->getStaticUrl();
```

### News feeds

News feeds are now implemented as page controllers. You can add new RSS, Atom and JSON feeds in the "pages" back end
module. The `{{news_feed:id}}` insert tag has been removed. You can use `{{link_url::id}}` instead.

### app.php

The old `app.php` entry point has been removed. Adjust your server configuration to use `index.php` instead.

### DCA "exclude" fields

The `exclude` property on DCA fields is no longer initialized when loading a back end module. Make sure to check for
`ContaoCorePermission::CAN_EDIT_FIELD_OF_TABLE` to know if a field should be available to a user.

### checkCredentials hook

The `checkCredentials` hook has been removed. Use the `CheckPassportEvent` instead.

### postLogin hook

The `postLogin` hook has been removed. Use the `LoginSuccessEvent` instead.

### importUser hook

The `importUser` hook has been removed. Implement a custom `UserProvider` service instead.

### postAuthenticate hook

The `postAuthenticate` hook has been removed. Use the `LoginSuccessEvent` instead.

### postLogout hook

The `postLogout` hook has been removed. Use the `LogoutEvent` instead.

### Contao 4 migrations

Contao 5 does not include any Contao 4 migrations, so make sure to upgrade to Contao 4.13 before upgrading to Contao 5!

### Install tool

The install tool has been removed. Use the `contao:setup`, `contao:migrate` and `contao:user:create` commands or the
Contao Manager instead.

### DataContainer callbacks

DataContainer callbacks registered via service tagging with a priority of `0` (which is the default) are now executed
after the existing callbacks instead of before.

### Insert tag flag uncached

The `|uncached` insert tag flag was removed. Use the `{{fragment::*}}` insert tag instead.

### Unknown insert tags

Unknown insert tags are no longer removed from the resulting text. Instead, they are now kept unchanged and are visible
in the front end.

### Insert tag hooks

The `$cache` parameter is no longer passed to the `replaceInsertTags` and the `insertTagFlags` hooks. An empty array is
passed instead.

### Figure

The `Contao\CoreBundle\Image\Studio\Figure::getLinkAttributes()` method will now return an
`Contao\CoreBundle\String\HtmlAttributes` object instead of an array. Use `iterator_to_array()` to transform it back to
an array representation. If you are just using array access, nothing needs to be changed.

To ease accessing metadata and lightbox results in a chained manner or in templates, the `getMetadata()` and
`getLightbox()` methods will now return `null` instead of throwing an exception if no data is available.

The `contao_figure` Twig function has been deprecated and replaced with the `figure` Twig function. The new function
returns a `Figure` object instead of a pre-rendered string which allows a more versatile application. To update existing
usages, render the `component/_figure.html.twig` template yourself by including or embedding it with the object:

```twig
{# before #}
{{ contao_figure('image.jpg', [800, 600]) }}

{# after #}
{% include "@Contao/component/_figure.html.twig" with {
    figure: figure('image.jpg', [800, 600])
} %}
```

### sqlCompileCommands hook

The `sqlCompileCommands` hook has been removed. Use the Doctrine DBAL `postGenerateSchema` event instead.

### CURRENT_ID

The `CURRENT_ID` constant and session variable have been removed. Use `DataContainer::$currentPid` instead to determine
the ID of the current parent record.

```php
$intCurrentParentRecordId = $dc->currentPid;
```

### Logout module

The deprecated logout module has been removed. Use the logout page instead.

### RequestToken class

The `RequestToken` class as well as the `disableRefererCheck` and `requestTokenWhitelist` settings have been removed.

### FORM_FIELDS

It is no longer possible to use the `FORM_FIELDS` mechanism to determine which form fields have been submitted. Make
sure to always submit at least an empty string in your widget:

```html
<!-- Wrong: the input will only be submitted if checked -->
<input type="checkbox" name="foo" value="bar">

<!-- Right: the input will always be submitted -->
<input type="hidden" name="foo" value=""><input type="checkbox" name="foo" value="bar">
```

### Constants

The constants `TL_ROOT`, `BE_USER_LOGGED_IN`, `FE_USER_LOGGED_IN`, `TL_START`, `TL_REFERER_ID`, `TL_SCRIPT`, `TL_MODE`
and `REQUEST_TOKEN`  have been removed.

Use the `kernel.project_dir` instead of `TL_ROOT`:

```php
$rootDir = System::getContainer()->getParameter('kernel.project_dir');
```

`BE_USER_LOGGED_IN` was historically used to preview unpublished elements in the front end. Use the token checker
service to check the separate cases instead:

```php
$hasBackendUser = System::getContainer()->get('contao.security.token_checker')->hasBackendUser();
$showUnpublished = System::getContainer()->get('contao.security.token_checker')->isPreviewMode();
```

Use the token checker service instead of `FE_USER_LOGGED_IN`:

```php
$hasFrontendUser = System::getContainer()->get('contao.security.token_checker')->hasFrontendUser();
```

Use the kernel start time instead of `TL_START`:

```php
$startTime = System::getContainer()->get('kernel')->getStartTime();
```

Use the request attribute `_contao_referer_id` instead of `TL_REFERER_ID`:

```php
$refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
```

Use the request stack to get the route instead of using `TL_SCRIPT`:

```php
$route = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_route');

if ('contao_backend' === $route) {
    // Do something
}
```

Use the `ScopeMatcher` service instead of using `TL_MODE`:

```php
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;

class Test {
    private $requestStack;
    private $scopeMatcher;

    public function __construct(RequestStack $requestStack, ScopeMatcher $scopeMatcher) {
        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function isBackend() {
        return $this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest());
    }

    public function isFrontend() {
        return $this->scopeMatcher->isFrontendRequest($this->requestStack->getCurrentRequest());
    }
}
```

Use the `contao.csrf.token_manager` service or the `requestToken` variable in your template instead of `REQUEST_TOKEN`:

```php
$requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
```

```php
<input type="hidden" name="REQUEST_TOKEN" value="<?= $this->requestToken ?>">
```

### TL_CRON

Cronjobs can no longer be registered via `$GLOBALS['TL_CRON']`. Use a service tagged with `contao.cronjob` instead (you
can also use the `@CronJob` annotation or `#[AsCronJob]` attribute). See the official developer documentation for more
details.

### Content elements

The following content element types have been rewritten as fragment controllers with Twig-only templates:

 - `code` (`ce_code` → `content_element/code`)
 - `headline` (`ce_headline` → `content_element/headline`)
 - `html` (`ce_html` → `content_element/html`)
 - `list` (`ce_list` → `content_element/list`)
 - `text` (`ce_text` → `content_element/text`)
 - `table` (`ce_table` → `content_element/table`)
 - `hyperlink` (`ce_hyperlink` → `content_element/hyperlink`)
 - `toplink` (`ce_toplink` → `content_element/toplink`)
 - `image` (`ce_image` → `content_element/image`)
 - `gallery` (`ce_gallery` → `content_element/gallery`)
 - `youtube` (`ce_youtube` → `content_element/youtube`)
 - `vimeo` (`ce_vimeo` → `content_element/vimeo`)
 - `downloads` (`ce_downloads` → `content_element/downloads`)
 - `download` (`ce_download` → `content_element/download`)
 - `player` (`ce_player` → `content_element/player`)
 - `teaser` (`ce_teaser` → `content_element/teaser`)

The legacy content elements and their templates are still around and will only be dropped in Contao 6. If you want to
use them instead of the new ones, you can opt in on a per-element basis by adding the respective lines to your
`system/config/localconfig.php`:

```php
// Restore legacy content elements
$GLOBALS['TL_CTE']['texts']['code'] = \Contao\ContentCode::class;
$GLOBALS['TL_CTE']['texts']['headline'] = \Contao\ContentHeadline::class;
$GLOBALS['TL_CTE']['texts']['html'] = \Contao\ContentHtml::class;
$GLOBALS['TL_CTE']['texts']['list'] = \Contao\ContentList::class;
$GLOBALS['TL_CTE']['texts']['text'] = \Contao\ContentText::class;
$GLOBALS['TL_CTE']['texts']['table'] = \Contao\ContentTable::class;
$GLOBALS['TL_CTE']['links']['hyperlink'] = \Contao\ContentHyperlink::class;
$GLOBALS['TL_CTE']['links']['toplink'] = \Contao\ContentToplink::class;
$GLOBALS['TL_CTE']['media']['image'] = \Contao\ContentImage::class;
$GLOBALS['TL_CTE']['media']['gallery'] = \Contao\ContentGallery::class;
$GLOBALS['TL_CTE']['media']['youtube'] = \Contao\ContentYouTube::class;
$GLOBALS['TL_CTE']['media']['vimeo'] = \Contao\ContentVimeo::class;
$GLOBALS['TL_CTE']['files']['downloads'] = \Contao\ContentDownloads::class;
$GLOBALS['TL_CTE']['files']['download'] = \Contao\ContentDownload::class;
$GLOBALS['TL_CTE']['media']['player'] = \Contao\ContentPlayer::class;
$GLOBALS['TL_CTE']['includes']['teaser'] = \Contao\ContentTeaser::class;
```

The following content elements and modules were already implemented as fragment controllers before. As of Contao 5.0,
they are Twig-only and also follow the new naming scheme:

- `template` content element (`ce_template` → `content_element/template`)
- `markdown` content element (`ce_markdown` → `content_element/markdown`)
- `template` module (`mod_template` → `frontend_module/template`)

### Show to guests only

The "show to guests only" function has been removed. Use the "protect page" function instead.

### tl_content.ptable

Contao no longer treats an empty `tl_content.ptable` column like it had been set to `tl_article`. Make sure to always
set the `ptable` column.

### disableInsertTags

The `disableInsertTags` config option has been removed. Use the `contao.insert_tags.allowed_tags` parameter instead.

### runonce.php

The support for `runonce.php` files has been dropped. Use the migration framework instead.

### onrestore_callback

The `onrestore_callback` has been removed. Use the `onrestore_version_callback` instead.

### getSearchablePages hook

The `getSearchablePages` hook has been removed. Use the `SitemapEvent` instead.

### Backend::addFileMetaInformationToRequest

`Backend::addFileMetaInformationToRequest()` and the corresponding `addFileMetaInformationToRequest` hook have been
removed. Use the image handling services and the `FileMetadataEvent` instead.

### FormTextarea->value

The value of the `FormTextarea` widget is no longer encoded with `specialchars()`. Encode the value in your custom
`form_textarea` templates instead.

### languages.php, getLanguages and $GLOBALS['TL_LANG']['LNG']

The `System::getLanguages()` method, the `getLanguages` hook and the `config/languages.php` file have been removed. Use
or decorate the `contao.intl.locales` service instead.

To add or remove countries, you can use the `contao.intl.locales` or `contao.intl.enabled_locales` configuration.
`$GLOBALS['TL_LANG']['LNG']` can still be used for overwriting translations, but no longer to retrieve language names.

### countries.php, getCountries and $GLOBALS['TL_LANG']['CNT']

The `System::getCountries()` method, the `getCountries` hook and the `config/countries.php` file have been removed. Use
or decorate the `contao.intl.countries` service instead.

To add or remove countries, you can use the `contao.intl.countries` configuration. `$GLOBALS['TL_LANG']['CNT']` can
still be used for overwriting translations, but no longer to retrieve country names.

### UnresolvableDependenciesException

The following classes and interfaces have been removed from the global namespace:

 - `listable`
 - `editable`
 - `executable`
 - `uploadable`
 - `UnresolvableDependenciesException`
 - `UnusedArgumentsException`

### Model

The protected `$arrClassNames` property was removed from the `Contao\Model` base class.

### Request

The `Contao\Request` library has been removed. Use another library such as `symfony/http-client` instead.

### Renamed resources

The following resources have been renamed:

 - `ContentMedia` → `ContentPlayer`
 - `FormCheckBox` → `FormCheckbox`
 - `FormRadioButton` → `FormRadio`
 - `FormSelectMenu` → `FormSelect`
 - `FormTextField` → `FormText`
 - `FormTextArea` → `FormTextarea`
 - `FormFileUpload` → `FormUpload`
 - `ModulePassword` → `ModuleLostPassword`
 - `form_textfield` → `form_text`

### CSS classes "first", "last", "even" and "odd"

The CSS classes `first`, `last`, `even`, `odd`, `row_*` and `col_*` are no longer applied anywhere. Use CSS selectors
instead.

### Template changes

The items in the `ce_list` and `ce_table` templates no longer consist of an associative array containing the item‘s CSS
class and content. Instead, it will only be the content.

```php
<!-- OLD -->
<?php foreach ($this->items as $item): ?>
  <li<?php if ($item['class']): ?> class="<?= $item['class'] ?>"<?php endif; ?>><?= $item['content'] ?></li>
<?php endforeach; ?>

<!-- NEW -->
<?php foreach ($this->items as $item): ?>
  <li><?= $item ?></li>
<?php endforeach; ?>
```

### Input type "textStore"

The `textStore` input type was removed. Use `password` instead.

### Global functions

The following global functions have been removed:

 - `scan()`
 - `specialchars()`
 - `standardize()`
 - `strip_insert_tags()`
 - `deserialize()`
 - `trimsplit()`
 - `ampersand()`
 - `nl2br_html5()`
 - `nl2br_xhtml()`
 - `nl2br_pre()`
 - `basename_natcasecmp()`
 - `basename_natcasercmp()`
 - `natcaseksort()`
 - `length_sort_asc()`
 - `length_sort_desc()`
 - `array_insert()`
 - `array_dupliacte()`
 - `array_move_up()`
 - `array_move_down()`
 - `array_delete()`
 - `array_is_assoc()`
 - `utf8_chr()`
 - `utf8_ord()`
 - `utf8_convert_encoding()`
 - `utf8_decode_entities()`
 - `utf8_chr_callback()`
 - `utf8_hexchr_callback()`
 - `utf8_detect_encoding()`
 - `utf8_romanize()`
 - `utf8_strlen()`
 - `utf8_strpos()`
 - `utf8_strrchr()`
 - `utf8_strrpos()`
 - `utf8_strstr()`
 - `utf8_strtolower()`
 - `utf8_strtoupper()`
 - `utf8_substr()`
 - `utf8_ucfirst()`
 - `utf8_str_split()`
 - `nl2br_callback()`

Most of them have alternatives in either `StringUtil`, `ArrayUtil` or may have PHP native alternatives such as the
`mb_*` functions. For advanced UTF-8 handling, use `symfony/string`.

### eval->orderField in PageTree and Picker widgets

Support for a separate database `orderField` column has been removed. Use `isSortable` instead which stores the order in
the same database column.

### Removed {{post::*}} insert tag

The `{{post::*}}` insert tag has been removed. To access submitted form data on forward pages, use the new
`{{form_session_data::*}}` insert tag instead.

### $_SESSION access no longer mapped to Symfony Session

The use of `$_SESSION` is discouraged because it makes testing and configuring alternative storage back ends hard. In
Contao 4, access to `$_SESSION` has been transparently mapped to the Symfony session. This has been removed. Use
`$request->getSession()` directly instead.

### database.sql files

Support for `database.sql` files has been dropped. Use DCA definitions and/or Doctrine DBAL schema listeners instead.

### Simple Token Parser

Tokens which are not valid PHP variable names (e.g. `##0foobar##`) are no longer supported by the Simple Token Parser.

### $GLOBALS['TL_KEYWORDS']

Keyword support in articles, and as such also `$GLOBALS['TL_KEYWORDS']`, has been removed.

### Legacy routing

The legacy routing has been dropped. As such, the `getPageIdFromUrl` and `getRootPageFromUrl` hooks do not exist
anymore. Use Symfony routing instead.

### Custom entry points

The `initialize.php` file has been removed, so custom entry points will no longer work. Register your entry points as
controllers instead.

### ClassLoader

The `Contao\ClassLoader` has been removed. Use Composer autoloading instead.

### Encryption

The `Contao\Encryption` class and the `eval->encrypt` DCA flag have been removed. Use `save_callback` and
`load_callback` and libraries such as `phpseclib/phpseclib` instead.

### Internal CSS editor

The internal CSS editor has been removed. Export your existing CSS files, import them in the file manager and then add
them as external CSS files to the page layout.

### log_message()

The function `log_message()` has been removed. Use the Symfony logger services instead. Consequently, the
`Automator::rotateLogs()` method has been removed, too.

### config.dataContainer

The DCA `config.dataContainer` property needs to be a FQCN instead of just `Table` or `Folder`.

More information: https://github.com/contao/contao/pull/4322

### pageSelector and fileSelector widgets

The back end widgets `pageSelector` and `fileSelector` have been removed. Use the `picker` widget instead.

### Public folder

The public folder is now called `public` by default. It can be renamed in the `composer.json` file.
