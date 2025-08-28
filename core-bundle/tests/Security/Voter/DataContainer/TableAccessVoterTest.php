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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TableAccessVoterTest extends TestCase
{
    private TableAccessVoter $voter;

    private AccessDecisionManagerInterface&MockObject $accessDecisionManager;

    private TokenInterface&MockObject $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new TableAccessVoter($this->accessDecisionManager);
        $this->token = $this->createMock(TokenInterface::class);

        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_MOD']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_MOD']);
    }

    public function testSupportsDCAttribute(): void
    {
        $this->assertTrue($this->voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX));
        $this->assertFalse($this->voter->supportsAttribute('foobar'));
    }

    public function testSupportsCRUDActionSubject(): void
    {
        $this->assertTrue($this->voter->supportsType(CreateAction::class));
        $this->assertTrue($this->voter->supportsType(UpdateAction::class));
        $this->assertTrue($this->voter->supportsType(ReadAction::class));
        $this->assertTrue($this->voter->supportsType(DeleteAction::class));
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

    public function testDeniesIfTableIsNotInAllowedModule(): void
    {
        $GLOBALS['BE_MOD']['content']['article']['tables'] = ['tl_foobar'];

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article')
            ->willReturn(false)
        ;

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testAbstainsIfTableIsAllowedInSecondaryModule(): void
    {
        $GLOBALS['BE_MOD']['content']['article']['tables'] = ['tl_foobar'];
        $GLOBALS['BE_MOD']['foo']['bar']['tables'] = ['tl_foobar'];

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

        $this->accessDecisionManager
            ->expects($this->exactly(2))
            ->method('decide')
            ->willReturnMap([
                [$this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article', false],
                [$this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'bar', true],
            ])
        ;

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testAbstainsIfExcludedFieldAccessIsGranted(): void
    {
        $GLOBALS['BE_MOD']['content']['article']['tables'] = ['tl_foobar'];

        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
                'exclude' => true,
            ],
        ];

        $this->accessDecisionManager
            ->expects($this->exactly(2))
            ->method('decide')
            ->willReturnMap([
                [$this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article', true],
                [$this->token, [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE], 'tl_foobar', true],
            ])
        ;

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testAbstainsIfDefaultExcludedFieldAccessIsGranted(): void
    {
        $GLOBALS['BE_MOD']['content']['article']['tables'] = ['tl_foobar'];

        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
            ],
        ];

        $this->accessDecisionManager
            ->expects($this->exactly(2))
            ->method('decide')
            ->willReturnMap([
                [$this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article', true],
                [$this->token, [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE], 'tl_foobar', true],
            ])
        ;

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testAbstainsIfAtLeastOneFieldIsNotExcluded(): void
    {
        $GLOBALS['BE_MOD']['content']['article']['tables'] = ['tl_foobar'];

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

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article')
            ->willReturn(true)
        ;

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new CreateAction('tl_foobar'), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testDeniesAccessIfFieldIsExcluded(): void
    {
        $GLOBALS['BE_MOD']['content']['article']['tables'] = ['tl_foobar'];

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

        $this->accessDecisionManager
            ->expects($this->exactly(2))
            ->method('decide')
            ->willReturnMap([
                [$this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article', true],
                [$this->token, [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE], 'tl_foobar', false],
            ])
        ;

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, new UpdateAction('tl_foobar', []), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }

    public function testDeniesAccessIfFieldIsDefaultExcluded(): void
    {
        $GLOBALS['BE_MOD']['content']['article']['tables'] = ['tl_foobar'];

        $GLOBALS['TL_DCA']['tl_foobar']['fields'] = [
            'foo' => [
                'inputType' => 'text',
                'exclude' => true,
            ],
            'bar' => [
                'inputType' => 'text',
            ],
        ];

        $this->accessDecisionManager
            ->expects($this->exactly(2))
            ->method('decide')
            ->willReturnMap([
                [$this->token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'article', true],
                [$this->token, [ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE], 'tl_foobar', false],
            ])
        ;

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, new UpdateAction('tl_foobar', []), [ContaoCorePermissions::DC_PREFIX.'tl_foobar']),
        );
    }
}
