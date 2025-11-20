<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Finder\Finder;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\Studio\CacheInvalidator;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Contao\CoreBundle\Twig\Studio\TemplateSkeleton;
use Contao\CoreBundle\Twig\Studio\TemplateSkeletonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class AbstractOperationTestCase extends TestCase
{
    public static function provideCommonContextsForExistingAndNonExistingUserTemplates(): \Generator
    {
        yield 'user template exists already' => [
            static::getOperationContext('content_element/existing_user_template'),
            true,
        ];

        yield 'user template in a theme exists already' => [
            static::getOperationContext('content_element/existing_user_template', 'my_theme'),
            true,
        ];

        yield 'user template does not yet exist' => [
            static::getOperationContext('content_element/no_user_template'),
            false,
        ];

        yield 'user template in a theme does not yet exist' => [
            static::getOperationContext('content_element/no_user_template', 'my_theme'),
            false,
        ];
    }

    public static function provideCommonThemeAndPathForNonExistingUserTemplate(): \Generator
    {
        yield 'no theme' => [
            null, 'content_element/no_user_template.html.twig',
        ];

        yield 'my theme' => [
            'my_theme', 'my/theme/content_element/no_user_template.html.twig',
        ];
    }

    public static function provideCommonThemeAndPathForExistingUserTemplate(): \Generator
    {
        yield 'no theme' => [
            null, 'content_element/existing_user_template.html.twig',
        ];

        yield 'my theme' => [
            'my_theme', 'my/theme/content_element/existing_user_template.html.twig',
        ];
    }

    protected function getContainer(ContaoFilesystemLoader|null $loader = null, VirtualFilesystemInterface|null $storage = null, Environment|null $twig = null, TemplateSkeletonFactory|null $skeletonFactory = null, CacheInvalidator|null $cacheInvalidator = null): Container
    {
        $loader ??= $this->mockContaoFilesystemLoader();
        $storage ??= $this->mockUserTemplatesStorage();
        $twig ??= $this->mockTwigEnvironment();
        $skeletonFactory ??= $this->mockTemplateSkeletonFactory();
        $cacheInvalidator ??= $this->mockCacheInvalidator();

        $container = new Container();
        $container->set('contao.twig.filesystem_loader', $loader);
        $container->set('contao.filesystem.virtual.user_templates', $storage);
        $container->set('twig', $twig);
        $container->set('contao.twig.studio.template_skeleton_factory', $skeletonFactory);
        $container->set('contao.twig.finder_factory', $this->mockTwigFinderFactory($loader));
        $container->set('contao.twig.studio.cache_invalidator', $cacheInvalidator);

        return $container;
    }

    protected static function getOperationContext(string $identifier, string|null $themeSlug = null): OperationContext
    {
        return new OperationContext(
            new ThemeNamespace(),
            $identifier,
            'html.twig',
            $themeSlug,
        );
    }

    /**
     * @return ContaoFilesystemLoader&MockObject
     */
    protected function mockContaoFilesystemLoader(): ContaoFilesystemLoader
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->method('getFirst')
            ->willReturnMap([
                ['content_element/existing_user_template', null, '@Contao_Global/content_element/existing_user_template.html.twig'],
                ['content_element/existing_user_template', 'my_theme', '@Contao_Theme_my_theme/content_element/existing_user_template.html.twig '],
                ['content_element/no_user_template', null, '@Contao_ContaoCoreBundle/content_element/no_user_template.html.twig'],
                ['content_element/no_user_template', 'my_theme', '@Contao_ContaoCoreBundle/content_element/no_user_template.html.twig'],
            ])
        ;

        return $loader;
    }

    /**
     * @return VirtualFilesystemInterface&MockObject
     */
    protected function mockUserTemplatesStorage(array|null $existingFiles = null): VirtualFilesystemInterface
    {
        $existingFiles ??= [
            'content_element/existing_user_template.html.twig',
            'my/theme/content_element/existing_user_template.html.twig',
        ];

        $storage = $this->createMock(VirtualFilesystemInterface::class);
        $storage
            ->method('fileExists')
            ->willReturnCallback(
                static fn (string $name) => \in_array($name, $existingFiles, true),
            )
        ;

        return $storage;
    }

    /**
     * @return TemplateSkeletonFactory&MockObject
     */
    protected function mockTemplateSkeletonFactory(string $basedOnName = '@Contao/content_element/no_user_template.html.twig'): TemplateSkeletonFactory
    {
        $factory = $this->createMock(TemplateSkeletonFactory::class);

        $skeleton = $this->createMock(TemplateSkeleton::class);
        $skeleton
            ->method('getContent')
            ->with($basedOnName)
            ->willReturn('new template content')
        ;

        $factory
            ->method('create')
            ->willReturn($skeleton)
        ;

        return $factory;
    }

    /**
     * @return FinderFactory&MockObject
     */
    protected function mockTwigFinderFactory(ContaoFilesystemLoader $loader): FinderFactory
    {
        $factory = $this->createMock(FinderFactory::class);

        $finder = new Finder(
            $loader,
            new ThemeNamespace(),
            $this->createMock(TranslatorInterface::class),
        );

        $factory
            ->method('create')
            ->willReturn($finder)
        ;

        return $factory;
    }

    /**
     * @return CacheInvalidator&MockObject
     */
    protected function mockCacheInvalidator(): CacheInvalidator
    {
        return $this->createMock(CacheInvalidator::class);
    }

    /**
     * @return Environment&MockObject
     */
    protected function mockTwigEnvironment(): Environment
    {
        return $this->createMock(Environment::class);
    }
}
