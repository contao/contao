<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\NewsletterBundle\Security\Voter\NewsletterFailuresAccessVoter;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class NewsletterFailuresAccessVoterTest extends TestCase
{
    /**
     * @dataProvider permissionProvider
     */
    public function testRequiresReadAccessToNewsletter(array|false $newsletter, bool $allowed, int $expected): void
    {
        $token = $this->createMock(TokenInterface::class);
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_newsletter WHERE id = ?', [42])
            ->willReturn($newsletter)
        ;

        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);

        if ($newsletter) {
            $decisionManager
                ->expects($this->once())
                ->method('decide')
                ->with($token, [ContaoCorePermissions::DC_PREFIX.'tl_newsletter'], new ReadAction('tl_newsletter', $newsletter))
                ->willReturn($allowed)
            ;
        } else {
            $decisionManager
                ->expects($this->never())
                ->method('decide')
            ;
        }

        $voter = new NewsletterFailuresAccessVoter($decisionManager, $connection);

        $this->assertSame($expected, $voter->vote($token, new ReadAction('tl_newsletter_failures', ['pid' => 42]), [ContaoCorePermissions::DC_PREFIX.'tl_newsletter_failures']));
    }

    public static function permissionProvider(): iterable
    {
        yield 'allowed newsletter' => [['id' => 42, 'pid' => 7], true, VoterInterface::ACCESS_ABSTAIN];
        yield 'forbidden newsletter' => [['id' => 42, 'pid' => 7], false, VoterInterface::ACCESS_DENIED];
        yield 'missing newsletter' => [false, true, VoterInterface::ACCESS_DENIED];
    }

    /**
     * @dataProvider writeActionProvider
     */
    public function testDeniesWriteActions(CreateAction|DeleteAction|UpdateAction $action): void
    {
        $decisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $decisionManager
            ->expects($this->never())
            ->method('decide')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('fetchAssociative')
        ;

        $voter = new NewsletterFailuresAccessVoter($decisionManager, $connection);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->createMock(TokenInterface::class), $action, [ContaoCorePermissions::DC_PREFIX.'tl_newsletter_failures']));
    }

    public static function writeActionProvider(): iterable
    {
        yield [new CreateAction('tl_newsletter_failures', ['pid' => 42])];
        yield [new UpdateAction('tl_newsletter_failures', ['pid' => 42], [])];
        yield [new DeleteAction('tl_newsletter_failures', ['pid' => 42])];
    }
}
