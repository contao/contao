<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\FilesModel;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Contao\RequestToken;
use Imagine\Gd\Imagine as ImagineGd;
use Imagine\Image\ImageInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Abstract TestCase class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Returns the path to the fixtures directory.
     *
     * @return string
     */
    public function getRootDir()
    {
        return __DIR__.DIRECTORY_SEPARATOR.'Fixtures';
    }

    /**
     * Returns the path to the fixtures cache directory.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->getRootDir().'/var/cache';
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param RequestStack|null    $requestStack
     * @param RouterInterface|null $router
     * @param array                $adapters
     * @param array                $instances
     *
     * @return ContaoFramework|\PHPUnit_Framework_MockObject_MockObject The object instance
     */
    public function mockContaoFramework(RequestStack $requestStack = null, RouterInterface $router = null, array $adapters = [], array $instances = [])
    {
        $container = $this->mockContainerWithContaoScopes();

        if (null === $requestStack) {
            $requestStack = $container->get('request_stack');
        }

        if (null === $router) {
            $router = $this->mockRouter('/index.html');
        }

        if (!isset($adapters[Config::class])) {
            $adapters[Config::class] = $this->mockConfigAdapter();
        }

        if (!isset($adapters[RequestToken::class])) {
            $adapters[RequestToken::class] = $this->mockRequestTokenAdapter();
        }

        if (!isset($adapters[FilesModel::class])) {
            $adapters[FilesModel::class] = $this->mockFilesModelAdapter();
        }

        /* @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder(ContaoFramework::class)
            ->setConstructorArgs([
                $requestStack,
                $router,
                $this->mockSession(),
                $this->mockScopeMatcher(),
                $this->getRootDir(),
                error_reporting(),
            ])
            ->setMethods(['getAdapter', 'createInstance'])
            ->getMock()
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($adapters) {
                return $adapters[$key];
            })
        ;

        $framework
            ->method('createInstance')
            ->willReturnCallback(function ($key) use ($instances) {
                return $instances[$key];
            })
        ;

        $framework->setContainer($container);

        return $framework;
    }

    /**
     * Mocks a Contao kernel.
     *
     * @return Kernel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockKernel()
    {
        $kernel = $this
            ->getMockBuilder(Kernel::class)
            ->setConstructorArgs(['test', false])
            ->getMock()
        ;

        $container = $this->mockContainerWithContaoScopes();

        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        return $kernel;
    }

    /**
     * Mocks a router returning the given URL.
     *
     * @param string $url
     *
     * @return RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockRouter($url)
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturn($url)
        ;

        return $router;
    }

    /**
     * Mocks a CSRF token manager.
     *
     * @return CsrfTokenManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockTokenManager()
    {
        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $tokenManager
            ->method('getToken')
            ->willReturn(new CsrfToken('_csrf', 'testValue'))
        ;

        $tokenManager
            ->method('refreshToken')
            ->willReturn(new CsrfToken('_csrf', 'testValue'))
        ;

        return $tokenManager;
    }

    /**
     * Mocks a Symfony session containing the Contao attribute bags.
     *
     * @return SessionInterface
     */
    protected function mockSession()
    {
        $session = new Session(new MockArraySessionStorage());
        $session->setId('session_test');
        $session->start();

        $beBag = new ArrayAttributeBag('_contao_be_attributes');
        $beBag->setName('contao_backend');

        $session->registerBag($beBag);

        $feBag = new ArrayAttributeBag('_contao_fe_attributes');
        $feBag->setName('contao_frontend');

        $session->registerBag($feBag);

        return $session;
    }

    /**
     * Mocks a request scope matcher.
     *
     * @return ScopeMatcher
     */
    protected function mockScopeMatcher()
    {
        return new ScopeMatcher(
            new RequestMatcher(null, null, null, null, ['_scope' => 'backend']),
            new RequestMatcher(null, null, null, null, ['_scope' => 'frontend'])
        );
    }

    /**
     * Mocks a container with scopes.
     *
     * @param string|null $scope
     *
     * @return Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockContainerWithContaoScopes($scope = null)
    {
        $container = new Container();
        $container->setParameter('kernel.cache_dir', $this->getCacheDir());
        $container->setParameter('kernel.project_dir', $this->getRootDir());
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('contao.web_dir', $this->getRootDir().'/web');
        $container->setParameter('contao.image.bypass_cache', false);
        $container->setParameter('contao.image.target_dir', $this->getRootDir().'/assets/images');
        $container->setParameter('contao.image.valid_extensions', ['jpg', 'svg', 'svgz']);

        $container->setParameter('contao.image.imagine_options', [
            'jpeg_quality' => 80,
            'interlace' => ImageInterface::INTERLACE_PLANE,
        ]);

        $container->set(
            'contao.resource_finder',
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao')
        );

        $container->set(
            'contao.resource_locator',
            new FileLocator($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao')
        );

        $request = new Request();

        if (null !== $scope) {
            $request->attributes->set('_scope', $scope);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container->set('request_stack', $requestStack);
        $container->set('session', $this->mockSession());
        $container->set('monolog.logger.contao', new NullLogger());

        $container->set(
            'contao.routing.backend_matcher',
            new RequestMatcher(null, null, null, null, ['_scope' => ContaoCoreBundle::SCOPE_BACKEND])
        );

        $container->set(
            'contao.routing.frontend_matcher',
            new RequestMatcher(null, null, null, null, ['_scope' => ContaoCoreBundle::SCOPE_FRONTEND])
        );

        $container->set(
            'contao.routing.scope_matcher',
            new ScopeMatcher(
                $container->get('contao.routing.backend_matcher'),
                $container->get('contao.routing.frontend_matcher')
            )
        );

        return $container;
    }

    /**
     * Mocks a config adapter.
     *
     * @param int|null $minPasswordLength
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockConfigAdapter($minPasswordLength = null)
    {
        $configAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($key) use ($minPasswordLength) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'timeZone':
                        return 'Europe/Berlin';

                    case 'gdMaxImgWidth':
                    case 'gdMaxImgHeight':
                        return 3000;

                    case 'minPasswordLength':
                        return $minPasswordLength;

                    case 'disableCron':
                        return false;

                    default:
                        return null;
                }
            })
        ;

        return $configAdapter;
    }

    /**
     * Mocks a request token adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockRequestTokenAdapter()
    {
        $rtAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', 'validate'])
            ->getMock()
        ;

        $rtAdapter
            ->method('get')
            ->willReturn('foobar')
        ;

        $rtAdapter
            ->method('validate')
            ->willReturn(true)
        ;

        return $rtAdapter;
    }

    /**
     * Mocks a files model adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockFilesModelAdapter()
    {
        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturn(null)
        ;

        return $adapter;
    }

    /**
     * Adds image services to the container.
     *
     * @param Container $container
     * @param string    $rootDir
     */
    protected function addImageServicesToContainer(Container $container, $rootDir = null)
    {
        $imagine = new ImagineGd();
        $imagineSvg = new ImagineSvg();
        $calculator = new ResizeCalculator();
        $filesystem = new Filesystem();
        $framework = $this->mockContaoFramework();

        if ($rootDir) {
            $container->setParameter('contao.web_dir', $rootDir.'/web');
            $container->setParameter('contao.image.target_dir', $rootDir.'/assets/images');
        }

        $resizer = new LegacyResizer($container->getParameter('contao.image.target_dir'), $calculator);
        $resizer->setFramework($framework);

        $imageFactory = new ImageFactory(
            $resizer,
            $imagine,
            $imagineSvg,
            $filesystem,
            $framework,
            $container->getParameter('contao.image.bypass_cache'),
            $container->getParameter('contao.image.imagine_options'),
            $container->getParameter('contao.image.valid_extensions')
        );

        $pictureGenerator = new PictureGenerator(
            $resizer,
            $container->getParameter('contao.image.bypass_cache'),
            ($rootDir ?: $this->getRootDir())
        );

        $pictureFactory = new PictureFactory(
            $pictureGenerator,
            $imageFactory,
            $framework,
            $container->getParameter('contao.image.bypass_cache'),
            $container->getParameter('contao.image.imagine_options')
        );

        $container->set('filesystem', $filesystem);
        $container->set('contao.image.imagine', $imagine);
        $container->set('contao.image.imagine_svg', $imagineSvg);
        $container->set('contao.image.resize_calculator', $calculator);
        $container->set('contao.image.resizer', $resizer);
        $container->set('contao.image.image_factory', $imageFactory);
        $container->set('contao.image.picture_generator', $pictureGenerator);
        $container->set('contao.image.picture_factory', $pictureFactory);
    }

    /**
     * Mocks a picker provider.
     *
     * @param string $class
     *
     * @return PickerMenuProviderInterface
     */
    protected function mockPickerProvider($class)
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturnCallback(function ($name, $params) {
                $url = $name;

                foreach ($params as $key => $value) {
                    $url .= ':'.$key.'='.$value;
                }

                return $url;
            })
        ;

        $user = $this
            ->getMockBuilder(BackendUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasAccess', 'getUser', 'getToken'])
            ->getMock()
        ;

        $user
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new $class($router, $requestStack, $tokenStorage, 'files');
    }
}
