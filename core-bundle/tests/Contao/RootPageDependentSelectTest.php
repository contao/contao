<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\PageModel;
use Contao\RootPageDependentSelect;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentSelectTest extends ContaoTestCase
{
    public function testRendersMultipleSelects(): void
    {
        $rootPages = [
            (object) ['id' => 1, 'title' => 'Root Page 1', 'language' => 'en'],
            (object) ['id' => 2, 'title' => 'Root Page 2', 'language' => 'de'],
            (object) ['id' => 3, 'title' => 'Root Page 3', 'language' => 'fr'],
        ];

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('root', ['order' => 'sorting'])
            ->willReturn($rootPages)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('tl_module.rootPageDependentModulesBlankOptionLabel', [], 'contao_module')
            ->willReturn('Please choose your module for %s')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('security.firewall.map', $this->createMock(FirewallMap::class));
        $container->set('security.token_storage', $this->createMock(TokenStorageInterface::class));
        $container->set('security.authentication.trust_resolver', $this->createMock(AuthenticationTrustResolverInterface::class));
        $container->set('security.access.simple_role_voter', $this->createMock(RoleVoter::class));
        $container->set('session', $this->createMock(SessionInterface::class));
        $container->set('filesystem', $this->createMock(Filesystem::class));
        $container->setParameter('contao.resources_paths', []);
        $container->set('contao.framework', $this->mockContaoFramework([PageModel::class => $pageAdapter]));
        $container->set('translator', $translator);

        System::setContainer($container);

        $fieldConfig = [
            'name' => 'rootPageDependentModules',
            'options' => [
                '10' => 'Module-10',
                '20' => 'Module-20',
                '30' => 'Module-30',
            ],
            'eval' => [
                'includeBlankOption' => true,
            ],
        ];

        $expectedOutput =
            <<<'OUTPUT'
                <select
                    name="rootPageDependentModules[]"
                    id="ctrl_rootPageDependentModules-1"
                    class="tl_select tl_chosen"
                    onfocus="Backend.getScrollOffset()"
                >
                    <option value="">Please choose your module for Root Page 1 (en)</option>
                    <option value="10">Module-10</option>
                    <option value="20">Module-20</option>
                    <option value="30">Module-30</option></select
                ><select
                    name="rootPageDependentModules[]"
                    id="ctrl_rootPageDependentModules-2"
                    class="tl_select tl_chosen"
                    onfocus="Backend.getScrollOffset()"
                >
                    <option value="">Please choose your module for Root Page 2 (de)</option>
                    <option value="10">Module-10</option>
                    <option value="20">Module-20</option>
                    <option value="30">Module-30</option></select
                ><select
                    name="rootPageDependentModules[]"
                    id="ctrl_rootPageDependentModules-3"
                    class="tl_select tl_chosen"
                    onfocus="Backend.getScrollOffset()"
                >
                    <option value="">Please choose your module for Root Page 3 (fr)</option>
                    <option value="10">Module-10</option>
                    <option value="20">Module-20</option>
                    <option value="30">Module-30</option>
                </select>
                OUTPUT;

        $minifiedExpectedOutput = preg_replace(['/\s\s|\n/', '/\s</'], ['', '<'], $expectedOutput);

        $widget = new RootPageDependentSelect(RootPageDependentSelect::getAttributesFromDca($fieldConfig, $fieldConfig['name']));

        $this->assertSame($minifiedExpectedOutput, $widget->generate());
    }
}
