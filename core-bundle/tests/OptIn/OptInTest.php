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
use Contao\MemberModel;
use Contao\Model;
use Contao\Model\Collection;
use Contao\OptInModel;
use Contao\TestCase\ContaoTestCase;

class OptInTest extends ContaoTestCase
{
    public function testCreatesAToken(): void
    {
        $model = $this->mockClassWithGetterSetter(OptInModel::class);
        $model
            ->expects($this->once())
            ->method('save')
        ;

        $model
            ->expects($this->once())
            ->method('setRelatedRecords')
            ->with(['tl_member' => 1])
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(OptInModel::class)
            ->willReturn($model)
        ;

        $token = (new OptIn($framework))->create('reg', 'foo@bar.com', ['tl_member' => 1]);

        $this->assertStringMatchesFormat('reg-%x', $token->getIdentifier());
        $this->assertTrue($token->isValid());
        $this->assertFalse($token->isConfirmed());
        $this->assertFalse($token->hasBeenSent());
    }

    public function testDoesNotCreateATokenIfThePrefixIsTooLong(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The token prefix must not be longer than 6 characters');

        (new OptIn($framework))->create('registration', 'foo@bar.com', ['tl_member' => 1]);
    }

    public function testFindsAToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class, ['token' => 'foobar']);

        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->expects($this->exactly(2))
            ->method('findByToken')
            ->willReturnOnConsecutiveCalls($model, null)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);
        $token = (new OptIn($framework))->find('foobar');

        $this->assertSame('foobar', $token->getIdentifier());
        $this->assertNull((new OptIn($framework))->find('barfoo'));
    }

    /**
     * @dataProvider getExpiredTokens
     */
    public function testPurgesExpiredTokens(string $method, ?Collection $model): void
    {
        $token = $this->createMock(OptInModel::class);
        $token
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_member' => 1])
        ;

        $token
            ->expects($this->once())
            ->method($method)
        ;

        $optInAdapter = $this->mockAdapter(['findExpiredTokens']);
        $optInAdapter
            ->expects($this->once())
            ->method('findExpiredTokens')
            ->willReturn([$token])
        ;

        $modelAdapter = $this->mockAdapter(['getClassFromTable']);
        $modelAdapter
            ->expects($this->once())
            ->method('getClassFromTable')
            ->willReturn(MemberModel::class)
        ;

        $memberAdapter = $this->mockAdapter(['findMultipleByIds']);
        $memberAdapter
            ->expects($this->once())
            ->method('findMultipleByIds')
            ->willReturn($model)
        ;

        $adapters = [
            OptInModel::class => $optInAdapter,
            Model::class => $modelAdapter,
            MemberModel::class => $memberAdapter,
        ];

        $framework = $this->mockContaoFramework($adapters);
        (new OptIn($framework))->purgeTokens();
    }

    /**
     * @return array<(string|Collection|null)[]>
     */
    public function getExpiredTokens(): array
    {
        return [
            ['delete', null],
            ['save', new Collection([$this->createMock(MemberModel::class)], 'tl_member')],
        ];
    }
}
