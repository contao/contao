<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inspector;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class ContaoInspectorNodeVisitorTest extends TestCase
{
    public function testHashLowPriority(): void
    {
        $this->assertSame(128, (new InspectorNodeVisitor(new NullAdapter()))->getPriority());
    }

    public function testAnalyzesSlots(): void
    {
        $environment = new Environment(
            new ArrayLoader(['template.html.twig' => '{% slot B %}{% block foo %}{% slot A %}{% slot A %}{% endblock %}']),
        );

        $cache = new ArrayAdapter();

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(TemplateHierarchyInterface::class),
                new InspectorNodeVisitor($cache),
                $this->createMock(ContaoCsrfTokenManager::class),
            ),
        );

        $inspector = new Inspector($environment, $cache);
        $information = $inspector->inspectTemplate('template.html.twig');

        $this->assertSame(['A', 'B'], $information->getSlots());
    }
}
