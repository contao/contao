# Contao 5 test case

Contao is an open source PHP content management system for people who want a professional website that is easy to
maintain. Visit the [project website][1] for more information.

The Contao 5 test case provides a PHPUnit test case with some useful methods for testing Contao. Run
`php composer.phar require --dev contao/test-case` to install the package and then use it in your test classes:

```php
use Contao\TestCase\ContaoTestCase;

class MyTest extends ContaoTestCase
{
}
```

## Mocking the Symfony container

The `getContainerWithContaoConfiguration()` method mocks a Symfony container with the default configuration of the
Contao core extension.

```php
$container = $this->getContainerWithContaoConfiguration();

echo $container->getParameter('contao.upload_path'); // will output "files"
```

You can also set a project directory:

```php
$container = $this->getContainerWithContaoConfiguration('/tmp');

echo $container->getParameter('kernel.project_dir'); // will output "/tmp"
echo $container->getParameter('kernel.root_dir'); // will output "/tmp/app"
echo $container->getParameter('kernel.cache_dir'); // will output "/tmp/var/cache"
```

## Mocking the Contao framework

The `mockContaoFramework)` method mocks an initialized Contao framework.

```php
$framework = $this->mockContaoFramework();
$framework
    ->expect($this->atLeastOnce())
    ->method('initialize')
;
```

The method automatically adds a Config adapter with the Contao settings:

```php
$framework = $this->mockContaoFramework();
$config = $framework->getAdapter(Contao\Config::class);

echo $config->get('datimFormat'); // will output "'Y-m-d H:i'"
```

You can optionally add more adapters as argument:

```php
$adapters = [
    Contao\Config::class => $configAdapter,
    Contao\Encryption::class => $encryptionAdapter,
];

$framework = $this->mockContaoFramework($adapters);
```

The given Config adapter will overwrite the default Config adapter.

## Mocking an adapter

The `mockAdapter()` method will mock an adapter with the given methods.

```php
$adapter = $this->mockAdapter(['findById']);
$adapter
    ->method('findById')
    ->willReturn($model)
;

$framework = $this->mockContaoFramework([Contao\FilesModel::class => $adapter]);
```

Adapters with a simple return value like above can be further simplified:

```php
$adapter = $this->mockConfiguredAdapter(['findById' => $model]);
```

This code does exactly the same as the code above.

## Mocking a class with magic properties

The `mockClassWithProperties()` method mocks a class that uses magic `__set()` and `__get()` methods to manage
properties.

```php
$mock = $this->mockClassWithProperties(Contao\PageModel::class);
$mock->id = 2;
$mock->title = 'Home';

echo $mock->title; // will output "Home"
```

If the class to be mocked is read-only, you can optionally pass the properties as constructor argument:

```php
$properties = [
    'id' => 2,
    'title' => 'Home',
];

$mock = $this->mockClassWithProperties(Contao\PageModel::class, $properties);

echo $mock->title; // will output "Home"
```

If you need to call a method of the original class, you can pass the method name as third argument. The resulting mock
object will be a partial mock object without the given method(s).

```php
$mock = $this->mockClassWithProperties(Contao\PageModel::class, [], ['getTable']);
$mock->id = 2;

echo $mock->getTable(); // will call the original method
```

## Mocking a token storage

The `mockTokenStorage()` mocks a token storage with a token returning either a Contao back end or front end user.

```php
$tokenStorage = $this->mockTokenStorage(Contao\BackendUser::class);
$user = $tokenStorage->getToken()->getUser();
```

## Using a temporary directory

The `getTempDir()` method creates a temporary directory based on the test class name and returns its path.

```php
$fs = new Filesystem();
$fs->mkdir($this->getTempDir().'/var/cache');
```

The directory will be removed automatically after the tests have been run. For this to work, please make sure to always
call the parent `tearDownAfterClass()` method if you define the method in your test class!

```php
use Contao\TestCase\ContaoTestCase;

class MyTest extends ContaoTestCase
{
    public static function tearDownAfterClass()
    {
        // The temporary directory would not be removed without this call!
        parent::tearDownAfterClass();
    }
}
```

[1]: https://contao.org
