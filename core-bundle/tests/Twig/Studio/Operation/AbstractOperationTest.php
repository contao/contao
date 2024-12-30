<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Contao\CoreBundle\Twig\Studio\TemplateSkeleton;
use Contao\CoreBundle\Twig\Studio\TemplateSkeletonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Twig\Environment;

abstract class AbstractOperationTest extends TestCase
{
    public function provideCommonContextsForExistingAndNonExistingUserTemplates(): \Generator
    {
        yield 'user template exists already' => [
            $this->getOperationContext('content_element/existing_user_template'),
            true,
        ];

        yield 'user template in a theme exists already' => [
            $this->getOperationContext('content_element/existing_user_template', 'my_theme'),
            true,
        ];

        yield 'user template does not yet exist' => [
            $this->getOperationContext('content_element/no_user_template'),
            false,
        ];

        yield 'user template in a theme does not yet exist' => [
            $this->getOperationContext('content_element/no_user_template', 'my_theme'),
            false,
        ];
    }

    public function provideCommonThemeAndPathForNonExistingUserTemplate(): \Generator
    {
        yield 'no theme' => [
            null, 'content_element/no_user_template.html.twig',
        ];

        yield 'my theme' => [
            'my_theme', 'my/theme/content_element/no_user_template.html.twig',
        ];
    }

    public function provideCommonThemeAndPathForExistingUserTemplate(): \Generator
    {
        yield 'no theme' => [
            null, 'content_element/existing_user_template.html.twig',
        ];

        yield 'my theme' => [
            'my_theme', 'my/theme/content_element/existing_user_template.html.twig',
        ];
    }

    protected function getContainer(ContaoFilesystemLoader|null $loader = null, VirtualFilesystemInterface|null $storage = null, Environment|null $twig = null): Container
    {
        $container = new Container();

        $container->set('contao.twig.filesystem_loader', $loader ?? $this->mockContaoFilesystemLoader());
        $container->set('contao.filesystem.virtual.user_templates', $storage ?? $this->mockUserTemplatesStorage());
        $container->set('contao.twig.studio.template_skeleton_factory', $this->mockTemplateSkeletonFactory());
        $container->set('twig', $twig ?? $this->mockTwigEnvironment());

        return $container;
    }

    protected function getOperationContext(string $identifier, string|null $themeSlug = null): OperationContext
    {
        return new OperationContext(
            new ThemeNamespace(),
            $identifier,
            'html.twig',
            $themeSlug,
        );
    }

    /**
     * @return ContaoFilesystemLoader&MockObject<ContaoFilesystemLoader>
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
     * @return VirtualFilesystemInterface&MockObject<VirtualFilesystemInterface>
     */
    protected function mockUserTemplatesStorage(): VirtualFilesystemInterface
    {
        $storage = $this->createMock(VirtualFilesystemInterface::class);
        $storage
            ->method('fileExists')
            ->willReturnMap([
                ['content_element/existing_user_template.html.twig', VirtualFilesystemInterface::NONE, true],
                ['content_element/no_user_template.html.twig', VirtualFilesystemInterface::NONE, false],
                ['my/theme/content_element/existing_user_template.html.twig', VirtualFilesystemInterface::NONE, true],
                ['my/theme/content_element/no_user_template.html.twig', VirtualFilesystemInterface::NONE, false],
            ])
        ;

        return $storage;
    }

    /**
     * @return TemplateSkeletonFactory&MockObject<TemplateSkeletonFactory>
     */
    protected function mockTemplateSkeletonFactory(): TemplateSkeletonFactory
    {
        $factory = $this->createMock(TemplateSkeletonFactory::class);

        $skeleton = $this->createMock(TemplateSkeleton::class);
        $skeleton
            ->method('getContent')
            ->with('@Contao/content_element/no_user_template.html.twig')
            ->willReturn('new template content')
        ;

        $factory
            ->method('create')
            ->willReturn($skeleton)
        ;

        return $factory;
    }

    /**
     * @return Environment&MockObject<Environment>
     */
    protected function mockTwigEnvironment(): Environment
    {
        return $this->createMock(Environment::class);
    }
}
