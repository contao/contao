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

use Contao\ArticleModel;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\ArticleContentVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ArticleContentVoterTest extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $voter = new ArticleContentVoter($this->mockContaoFramework(), $this->createMock(AccessDecisionManagerInterface::class));

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_content'));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertFalse($voter->supportsAttribute('foobar'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_page'));
        $this->assertFalse($voter->supportsType(CreateAction::class));
        $this->assertFalse($voter->supportsType(UpdateAction::class));
        $this->assertFalse($voter->supportsType(DeleteAction::class));
    }

    /**
     * @dataProvider voterProvider
     */
    public function testVoter(array $current, bool|null $accessGranted, int $expected): void
    {
        $current['pid'] = 42;
        $article = $this->mockClassWithProperties(ArticleModel::class, ['id' => 42, 'pid' => 21]);

        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->method('findByPk')
            ->with(42)
            ->willReturn($article)
        ;

        $framework = $this->mockContaoFramework([ArticleModel::class => $adapter]);

        $token = $this->createMock(TokenInterface::class);
        $subject = new ReadAction('tl_article', $current);

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);

        if (null === $accessGranted) {
            $decisionManager
                ->expects($this->never())
                ->method('decide')
            ;
        } else {
            $decisionManager
                ->expects($this->once())
                ->method('decide')
                ->with($token, [ContaoCorePermissions::USER_CAN_EDIT_ARTICLES], 21)
                ->willReturn($accessGranted)
            ;
        }

        $voter = new ArticleContentVoter($framework, $decisionManager);
        $result = $voter->vote($token, $subject, [ContaoCorePermissions::DC_PREFIX.'tl_content']);

        $this->assertSame($expected, $result);
    }

    public function voterProvider(): \Generator
    {
        yield 'Abstains when access is allowed' => [
            ['ptable' => 'tl_article'],
            true,
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Only votes on ptable=tl_article' => [
            ['ptable' => 'tl_news'],
            null,
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'Denies access if decision manager denies' => [
            ['ptable' => 'tl_article'],
            false,
            VoterInterface::ACCESS_DENIED,
        ];
    }
}
