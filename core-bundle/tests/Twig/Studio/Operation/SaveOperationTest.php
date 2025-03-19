<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\TemplateInformation;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Contao\CoreBundle\Twig\Studio\Operation\SaveOperation;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Source;

class SaveOperationTest extends AbstractOperationTest
{
    /**
     * @dataProvider provideCommonContextsForExistingAndNonExistingUserTemplates
     */
    public function testCanExecute(OperationContext $context, bool $userTemplateExists): void
    {
        $this->assertSame(
            $userTemplateExists,
            $this->getSaveOperation()->canExecute($context),
        );
    }

    /**
     * @dataProvider provideCommonThemeAndPathForNonExistingUserTemplate
     */
    public function testFailToSaveUserTemplateBecauseItDoesNotExists(string|null $themeSlug): void
    {
        $storage = $this->mockUserTemplatesStorage();
        $storage
            ->expects($this->never())
            ->method('write')
        ;

        $twig = $this->mockTwigEnvironment();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/template_studio/operation/default_result.stream.html.twig',
                $this->anything(),
            )
            ->willReturn('error.stream')
        ;

        $operation = $this->getSaveOperation($storage, $twig);

        $response = $operation->execute(
            new Request(),
            $this->getOperationContext('content_element/no_user_template', $themeSlug),
        );

        $this->assertSame('error.stream', $response->getContent());
    }

    /**
     * @dataProvider provideCommonThemeAndPathForExistingUserTemplate
     */
    public function testSaveUserTemplate(string|null $themeSlug, string $path): void
    {
        $storage = $this->mockUserTemplatesStorage();
        $storage
            ->expects($this->once())
            ->method('write')
            ->with($path, '<updated code>')
        ;

        $twig = $this->mockTwigEnvironment();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/template_studio/operation/save_result.stream.html.twig',
                ['identifier' => 'content_element/existing_user_template', 'full_reload' => false],
            )
            ->willReturn('save_result.stream')
        ;

        $operation = $this->getSaveOperation($storage, $twig);

        $response = $operation->execute(
            new Request(request: ['code' => '<updated code>']),
            $this->getOperationContext('content_element/existing_user_template', $themeSlug),
        );

        $this->assertSame('save_result.stream', $response->getContent());
    }

    public function testThrowsExceptionIfTheTemplateCodeIsMissingWhenSaving(): void
    {
        $storage = $this->mockUserTemplatesStorage();
        $storage
            ->expects($this->never())
            ->method('write')
        ;

        $twig = $this->mockTwigEnvironment();

        $operation = $this->getSaveOperation($storage, $twig);
        $context = $this->getOperationContext('content_element/existing_user_template');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The request did not contain the template code.');

        $operation->execute(new Request(), $context);
    }

    private function getSaveOperation(VirtualFilesystemInterface|null $storage = null, Environment|null $twig = null): SaveOperation
    {
        $templateInformation = new TemplateInformation(
            new Source(
                '<code>',
                '@Contao_Global/content_element/existing_user_template.html.twig',
                '<path_to>/content_element/existing_user_template.html.twig',
            ),
        );

        $inspector = $this->createMock(Inspector::class);
        $inspector
            ->method('inspectTemplate')
            ->willReturnMap([
                [
                    '@Contao/content_element/existing_user_template.html.twig',
                    new TemplateInformation(
                        new Source(
                            '<code>',
                            '@Contao_Global/content_element/existing_user_template.html.twig',
                            '<path_to>/content_element/existing_user_template.html.twig',
                        ),
                    ),
                ],
                [
                    '@Contao/content_element/existing_user_template.html.twig',
                    new TemplateInformation(
                        new Source(
                            '<code>',
                            '@Contao_ContaoCoreBundle/content_element/no_user_template.html.twig',
                            '<path_to>/content_element/no_user_template.html.twig',
                        ),
                    ),
                ],
            ])
            ->willReturn($templateInformation)
        ;

        $operation = new SaveOperation($inspector);
        $operation->setContainer($this->getContainer(null, $storage, $twig));
        $operation->setName('save');

        return $operation;
    }
}
