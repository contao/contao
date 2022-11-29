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
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class DeprecationsNodeVisitorTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testHashHighPriority(): void
    {
        $this->assertSame(10, (new DeprecationsNodeVisitor())->getPriority());
    }

    /**
     * @group legacy
     */
    public function testTriggersInsertTagDeprecation(): void
    {
        $templateContent = '<a href="{{ \'{{link_url::9}}\' }}">Test</a>';
        $environment = $this->getEnvironment($templateContent);

        $this->expectDeprecation('%sYou should not rely on insert tags being replaced in the rendered HTML.%s');

        $environment->render('template.html.twig');
    }

    private function getEnvironment(string $templateContent): Environment
    {
        $environment = new Environment(
            new ArrayLoader(['template.html.twig' => $templateContent])
        );

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(TemplateHierarchyInterface::class),
                $this->createMock(ContaoCsrfTokenManager::class)
            )
        );

        return $environment;
    }
}
