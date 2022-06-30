# Deprecated features

## Base tag

Relying on the `<base>` tag has been deprecated in Contao 5.0 and will no longer work in Contao 6. Use absolute paths
for links and assets instead.

## kernel.packages

The `kernel.packages` parameter has been deprecated in Contao 4.5 and will be removed in Contao 5.0. Use the
`Composer\InstalledVersions` class instead.

```php
$coreVersion = InstalledVersions::getPrettyVersion('contao/core-bundle');
```

## TL_ASSETS_URL and TL_FILES_URL

The constants `TL_ASSETS_URL` and `TL_FILES_URL` have been deprecated in Contao 4.5 and will be removed in Contao 5.0.
Use the assets or files context instead:

```php
// Old syntax
echo TL_ASSETS_URL;
echo TL_FILES_URL;

// New syntax
$container = System::getContainer();
echo $container->get('contao.assets.assets_context')->getStaticUrl();
echo $container->get('contao.assets.files_context')->getStaticUrl();
```

## Image service

The `Image` and `Picture` classes have been deprecated in favor of the image and picture services. Here are three
examples of how to use the services:

### Image::get()

```php
// Old syntax
$image = Image::get($objSubfiles->path, 80, 60, 'center_center');

// New syntax
$container = System::getContainer();
$rootDir = $container->getParameter('kernel.project_dir');

$image = $container
    ->get('contao.image.factory')
    ->create($rootDir.'/'.$objSubfiles->path, [80, 60, 'center_center'])
    ->getUrl($rootDir)
;
```

### Image::create()

```php
// Old syntax
$image = Image::create($path, [400, 50, 'box'])
    ->executeResize()
    ->getResizedPath()
;

// New syntax
$container = System::getContainer();
$rootDir = $container->getParameter('kernel.project_dir');

$image = $container
    ->get('contao.image.factory')
    ->create(
        $rootDir.'/'.$path,
        (new ResizeConfiguration())
            ->setWidth(400)
            ->setHeight(50)
            ->setMode(ResizeConfiguration::MODE_BOX)
    )
    ->getUrl($rootDir)
;
```

### Picture::create()

```php
// Old syntax
$data = Picture::create($path, $imageSize)->getTemplateData();

// New syntax
$container = System::getContainer();
$rootDir = $container->getParameter('kernel.project_dir');

$picture = $container
    ->get('contao.image.picture_factory')
    ->create($rootDir.'/'.$path, $imageSize)
;

$data = [
    'img' => $picture->getImg($rootDir),
    'sources' => $picture->getSources($rootDir),
];
```

More information: https://github.com/contao/image/blob/master/README.md

## Page handler without getResponse()

Using a custom page handler without a `getResponse()` method has been deprecated in Contao 4.0 and will no longer work
in Contao 5.0.

## VERSION and BUILD

The `VERSION` and `BUILD` constants have been deprecated in Contao 4.0 and will be removed in Contao 5.0. Use the
`ContaoCoreBundle::getVersion()` method instead.

```php
$coreVersion = ContaoCoreBundle::getVersion();
```

## member_grouped.html5

Accessing the field groups via one of the following properties in the `member_grouped.html5` template has been
deprecated in Contao 4.0 and will no longer work in Contao 5.0:

 * `$this->personal`
 * `$this->address`
 * `$this->contact`
 * `$this->login`
 * `$this->captcha`

Use `$this->categories` instead.

## "channel" token

Using the simple token "channel" in newsletter subscription mails has been deprecated in Contao 4.0 and will no longer
work in Contao 5.0. Use the "channels" token instead.

## $this->arrCache

Using `$this->arrCache`, which is defined in the `System` class, has been deprecated in Contao 4.0 and will no longer
work in Contao 5.0. If you are using it in your class, make sure to define it as property.

## $this->items in pagination templates

Using `$this->items` in pagination templates has been deprecated in Contao 4.0 and will no longer work in Contao 5.0.
Use `$this->pages` instead.

## TL_SCRIPT_URL and TL_PLUGINS_URL

The constants `TL_SCRIPT_URL` and `TL_PLUGINS_URL` have been deprecated in Contao 4.0 and will be removed in Contao 5.0.
Use `TL_ASSETS_URL` instead.

## $this->language in TinyMCE config files

Using `$this->language` in TinyMCE configuration files has been deprecated in Contao 4.0 and will no longer work in
Contao 5.0. Use the static method `Backend::getTinyMceLanguage()` instead.

```php
$locale = System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
```

## Request.Mixed (JavaScript)

Using the old Request.Mixed class instead of Request.Contao has been deprecated in Contao 4.0 and will no longer work in
Contao 5.0.

## "subpalette" event (JavaScript)

The "subpalette" event, which is currently fired when a subpalette is toggled via Ajax, has been deprecated in Contao
4.0 and will be removed in Contao 5.0. Subscribe to the "ajax_change" event instead.

## Session class

The `Session` class has been deprecated in Contao 4.0 and will be removed in Contao 5.0. Use the session service
instead:

```php
$session = System::getContainer()->get('session');
```

## Widget::addSubmit()

The `Widget::addSubmit()` method has been deprecated in Contao 4.0 and will be removed in Contao 5.0. It already does
not add a submit button anymore.

## Contao class loader

Even though we are still using the Contao class loader, it has been deprecated in favor of the Composer class loader.
You should no longer use it, and you can no longer use it to override arbitrary core classes.

## Using $this in configuration files

Using `$this` in configuration files such as `config/config.php` or `dca/*.php` has been deprecated in Contao 4.0 and
will no longer work in Contao 5.0.

You can use the static helper methods such as `System::loadLanguageFile()` or `Controller::loadDataContainer()` instead.

## PHP entry points

Contao 4 only uses a single PHP entry point, namely the `index.php` or `preview.php` file. The previous PHP entry points
have been removed and a route has been set up for each one instead (see UPGRADE.md).

Using the old paths is deprecated and will no longer work in Contao 5.0.
