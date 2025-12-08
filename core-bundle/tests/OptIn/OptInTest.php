<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\OptIn;

use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Tests\TestCase;
use Contao\MemberModel;
use Contao\Model;
use Contao\Model\Collection;
use Contao\OptInModel;

class OptInTest extends TestCase
{
    public function testCreatesAToken(): void
    {
        $model = $this->createClassWithPropertiesStub(OptInModel::class);
        $model
            ->expects($this->once())
            ->method('save')
        ;

        $model
            ->expects($this->once())
            ->method('setRelatedRecords')
            ->with(['tl_member' => [1]])
        ;

        $framework = $this->createContaoFrameworkStub();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(OptInModel::class)
            ->willReturn($model)
        ;

        $token = (new OptIn($framework))->create('reg', 'foo@bar.com', ['tl_member' => [1]]);

        $this->assertStringMatchesFormat('reg-%x', $token->getIdentifier());
        $this->assertTrue($token->isValid());
        $this->assertFalse($token->isConfirmed());
        $this->assertFalse($token->hasBeenSent());
    }

    public function testDoesNotCreateATokenIfThePrefixIsTooLong(): void
    {
        $framework = $this->createContaoFrameworkStub();
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The token prefix must not be longer than 6 characters');

        (new OptIn($framework))->create('registration', 'foo@bar.com', ['tl_member' => [1]]);
    }

    public function testFindsAToken(): void
    {
        $model = $this->createClassWithPropertiesStub(OptInModel::class);
        $model->token = 'foobar';

        $adapter = $this->createAdapterStub(['findByToken']);
        $adapter
            ->expects($this->exactly(2))
            ->method('findByToken')
            ->willReturnOnConsecutiveCalls($model, null)
        ;

        $framework = $this->createContaoFrameworkStub([OptInModel::class => $adapter]);
        $token = (new OptIn($framework))->find('foobar');

        $this->assertSame('foobar', $token->getIdentifier());
        $this->assertNull((new OptIn($framework))->find('barfoo'));
    }

    public function testPurgesExpiredTokens(): void
    {
        $properties = [
            'confirmedOn' => strtotime('yesterday'),
        ];

        $token = $this->createClassWithPropertiesStub(OptInModel::class, $properties);
        $token
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_member' => 1])
        ;

        $token
            ->expects($this->once())
            ->method('delete')
        ;

        $optInAdapter = $this->createAdapterStub(['findExpiredTokens']);
        $optInAdapter
            ->expects($this->once())
            ->method('findExpiredTokens')
            ->willReturn([$token])
        ;

        $modelAdapter = $this->createAdapterStub(['getClassFromTable']);
        $modelAdapter
            ->expects($this->once())
            ->method('getClassFromTable')
            ->willReturn(MemberModel::class)
        ;

        $memberAdapter = $this->createAdapterStub(['findMultipleByIds']);
        $memberAdapter
            ->expects($this->once())
            ->method('findMultipleByIds')
            ->willReturn(null)
        ;

        $adapters = [
            OptInModel::class => $optInAdapter,
            Model::class => $modelAdapter,
            MemberModel::class => $memberAdapter,
        ];

        $framework = $this->createContaoFrameworkStub($adapters);
        (new OptIn($framework))->purgeTokens();
    }

    public function testProlongsExpiredTokens(): void
    {
        $properties = [
            'removeOn' => strtotime('today'),
            'confirmedOn' => strtotime('yesterday'),
        ];

        $token = $this->createClassWithPropertiesStub(OptInModel::class, $properties);
        $token
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_member' => 1])
        ;

        $token
            ->expects($this->once())
            ->method('save')
        ;

        $optInAdapter = $this->createAdapterStub(['findExpiredTokens']);
        $optInAdapter
            ->expects($this->once())
            ->method('findExpiredTokens')
            ->willReturn([$token])
        ;

        $modelAdapter = $this->createAdapterStub(['getClassFromTable']);
        $modelAdapter
            ->expects($this->once())
            ->method('getClassFromTable')
            ->willReturn(MemberModel::class)
        ;

        $memberAdapter = $this->createAdapterStub(['findMultipleByIds']);
        $memberAdapter
            ->expects($this->once())
            ->method('findMultipleByIds')
            ->willReturn(new Collection([$this->createMock(MemberModel::class)], 'tl_member'))
        ;

        $adapters = [
            OptInModel::class => $optInAdapter,
            Model::class => $modelAdapter,
            MemberModel::class => $memberAdapter,
        ];

        $framework = $this->createContaoFrameworkStub($adapters);
        (new OptIn($framework))->purgeTokens();

        $this->assertSame(strtotime('+3 years', $properties['removeOn']), $token->removeOn);
    }

    public function testPurgesUnconfirmedTokens(): void
    {
        $properties = [
            'confirmedOn' => 0,
        ];

        $token = $this->createClassWithPropertiesStub(OptInModel::class, $properties);
        $token
            ->expects($this->never())
            ->method('getRelatedRecords')
        ;

        $token
            ->expects($this->once())
            ->method('delete')
        ;

        $optInAdapter = $this->createAdapterStub(['findExpiredTokens']);
        $optInAdapter
            ->expects($this->once())
            ->method('findExpiredTokens')
            ->willReturn([$token])
        ;

        $modelAdapter = $this->createAdapterStub(['getClassFromTable']);
        $modelAdapter
            ->expects($this->never())
            ->method('getClassFromTable')
        ;

        $adapters = [
            OptInModel::class => $optInAdapter,
            Model::class => $modelAdapter,
        ];

        $framework = $this->createContaoFrameworkStub($adapters);
        (new OptIn($framework))->purgeTokens();
    }
}
