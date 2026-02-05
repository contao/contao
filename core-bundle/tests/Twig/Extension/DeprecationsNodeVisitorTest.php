<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Extension\DeprecationsNodeVisitor;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class DeprecationsNodeVisitorTest extends TestCase
{
    public function testHashHighPriority(): void
    {
        $this->assertSame(10, (new DeprecationsNodeVisitor())->getPriority());
    }

    public function testTriggersInsertTagDeprecation(): void
    {
        $templateContent = '<a href="{{ \'{{link_url::9}}\' }}">Test</a>';
        $environment = $this->getEnvironment($templateContent);

        $this->expectUserDeprecationMessageMatches('/You should not rely on insert tags being replaced in the rendered HTML\./');

        $environment->render('template.html.twig');
    }

    private function getEnvironment(string $templateContent): Environment
    {
        $environment = new Environment(
            new ArrayLoader(['template.html.twig' => $templateContent]),
        );

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createStub(ContaoFilesystemLoader::class),
                $this->createStub(ContaoCsrfTokenManager::class),
                $this->createStub(ContaoVariable::class),
                new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
            ),
        );

        return $environment;
    }
}
