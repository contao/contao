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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\TableAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TableAccessVoterTest extends TestCase
{
    private TableAccessVoter $voter;

    /**
     * @var Security&MockObject
     */
    private Security $security;

    /**
     * @var TokenInterface&MockObject
     */
    private TokenInterface $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->security = $this->createMock(Security::class);
        $this->voter = new TableAccessVoter($this->security);
        $this->token = $this->createMock(TokenInterface::class);

        unset($GLOBALS['TL_DCA']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testSupportsDCAttribute(): void
    {
        $this->assertTrue($this->voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX));
        $this->assertFalse($this->voter->supportsAttribute('foobar'));
    }

    public function testSupportsCreateAndUpdateActionSubject(): void
    {
        $this->assertTrue($this->voter->supportsType(CreateAction::class));
        $this->assertTrue($this->voter->supportsType(UpdateAction::class));
        $this->assertFalse($this->voter->supportsType(ReadAction::class));
        $this->assertFalse($this->voter->supportsType(DeleteAction::class));
        $this->assertFalse($this->voter->supportsType('foobar'));
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('foobar'), ['foobar']),
        );

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new UpdateAction('foobar', []), ['foobar']),
        );
    }

    public function testAbstainsIfExcludedFieldAccessIsGranted(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
                'exclude' => true,
            ],
        ];

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_foobar')
            ->willReturn(true)
        ;

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testAbstainsIfDefaultExcludedFieldAccessIsGranted(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
            ],
        ];

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_foobar')
            ->willReturn(true)
        ;

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testAbstainsIfAtLeastOneFieldIsNotExcluded(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
                'exclude' => true,
            ],
            'bar' => [
                'inputType' => 'text',
                'exclude' => false,
            ],
        ];

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testDeniesAccessIfFieldIsExcluded(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
                'exclude' => true,
            ],
            'bar' => [
                'inputType' => 'text',
                'exclude' => true,
            ],
        ];

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_foobar')
            ->willReturn(false)
        ;

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, new UpdateAction('tl_foobar', []), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testDeniesAccessIfFieldIsDefaultExcluded(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
                'exclude' => true,
            ],
            'bar' => [
                'inputType' => 'text',
            ],
        ];

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_foobar')
            ->willReturn(false)
        ;

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, new UpdateAction('tl_foobar', []), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }
}
