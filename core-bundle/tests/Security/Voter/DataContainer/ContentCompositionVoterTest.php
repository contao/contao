<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter\DataContainer;

use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\ContentCompositionVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\LayoutModel;
use Contao\PageModel;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContentCompositionVoterTest extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $framework = $this->mockContaoFramework();
        $pageRegistry = $this->createMock(PageRegistry::class);

        $voter = new ContentCompositionVoter($framework, $pageRegistry);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_article'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertFalse($voter->supportsAttribute('foobar'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_page'));
        $this->assertFalse($voter->supportsType(ReadAction::class));
        $this->assertFalse($voter->supportsType(DeleteAction::class));
    }

    public function testDeniesAccessIfPageModelIsNotFound(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new CreateAction('tl_article', ['pid' => 42]);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->never())
            ->method('supportsContentComposition')
        ;

        $voter = new ContentCompositionVoter($framework, $pageRegistry);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_article']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessIfPageDoesNotSupportContentComposition(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new CreateAction('tl_article', ['pid' => 42]);

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel
            ->expects($this->never())
            ->method('loadDetails')
        ;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($pageModel)
            ->willReturn(false)
        ;

        $voter = new ContentCompositionVoter($framework, $pageRegistry);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_article']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessIfPageLayoutIsNotFound(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new CreateAction('tl_article', ['pid' => 42]);

        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageModel
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $pageModel
            ->expects($this->once())
            ->method('getRelated')
            ->with('layout')
            ->willReturn(null)
        ;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($pageModel)
            ->willReturn(true)
        ;

        $voter = new ContentCompositionVoter($framework, $pageRegistry);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_article']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessIfPageLayoutHasNoArticleModule(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new CreateAction('tl_article', ['pid' => 42]);

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['modules' => serialize([])]);

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $pageModel
            ->expects($this->once())
            ->method('getRelated')
            ->with('layout')
            ->willReturn($layoutModel)
        ;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($pageModel)
            ->willReturn(true)
        ;

        $voter = new ContentCompositionVoter($framework, $pageRegistry);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_article']);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }



    public function testAbstainsIfPageLayoutHasArticleModule(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new CreateAction('tl_article', ['pid' => 42]);

        $modules = [['mod' => 0]];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['modules' => serialize($modules)]);

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $pageModel
            ->expects($this->once())
            ->method('getRelated')
            ->with('layout')
            ->willReturn($layoutModel)
        ;

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($pageModel)
            ->willReturn(true)
        ;

        $voter = new ContentCompositionVoter($framework, $pageRegistry);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_article']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
