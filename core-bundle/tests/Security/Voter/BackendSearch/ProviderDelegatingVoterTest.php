<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Security\Voter\BackendSearch;

use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\Voter\BackendSearch\ProviderDelegatingVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ProviderDelegatingVoterTest extends TestCase
{
    /**
     * @dataProvider notSupportedProvider
     */
    public function testNotSupported(mixed $subject, array $attributes): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->never())
            ->method('supportsType')
            ->willReturn(true)
        ;

        $voter = new ProviderDelegatingVoter([$provider]);
        $result = $voter->vote($this->createMock(TokenInterface::class), $subject, $attributes);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    /**
     * @dataProvider supportedChecksCorrectlyProvider
     */
    public function testSupportedChecksCorrectly(bool $accessGranted): void
    {
        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->exactly(2))
            ->method('supportsType')
            ->willReturn(true)
        ;

        $provider
            ->expects($this->once())
            ->method('isHitGranted')
            ->willReturn($accessGranted)
        ;

        $voter = new ProviderDelegatingVoter([$provider]);
        $result = $voter->vote(
            $this->createMock(TokenInterface::class),
            new Hit(new Document('id', 'type', 'searchable content'), 'title', 'https://example.com?view=true'),
            [ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_HIT],
        );

        $this->assertSame($accessGranted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED, $result);
    }

    public static function notSupportedProvider(): iterable
    {
        yield 'Both, subject and attributes do not match' => [
            'foobar',
            ['foobar'],
        ];

        yield 'Subject does not match' => [
            'foobar',
            [ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_HIT],
        ];

        yield 'Attributes does not match' => [
            new Hit(new Document('id', 'type', 'searchable content'), 'title', 'https://example.com?view=true'),
            ['foobar'],
        ];
    }

    public static function supportedChecksCorrectlyProvider(): iterable
    {
        yield [true];
        yield [false];
    }
}
