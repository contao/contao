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
use Contao\Email;
use Contao\MemberModel;
use Contao\Model;
use Contao\OptInModel;
use Contao\TestCase\ContaoTestCase;

class OptInTest extends ContaoTestCase
{
    public function testCreatesAToken(): void
    {
        $model = $this->createMock(OptInModel::class);
        $model
            ->expects($this->once())
            ->method('save')
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(OptInModel::class)
            ->willReturn($model)
        ;

        $token = (new OptIn($framework))->create('reg-', 'tl_member', 1, 'foo@bar.com', 'Subject', 'Text');

        $this->assertStringMatchesFormat('reg-%x', $token);
    }

    public function testConfirmsAToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class, ['confirmedOn' => 0]);
        $model
            ->expects($this->once())
            ->method('save')
        ;

        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        (new OptIn($framework))->confirm('foobar');
    }

    public function testDoesNotConfirmAnInvalidToken(): void
    {
        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid token: foobar');

        (new OptIn($framework))->confirm('foobar');
    }

    public function testDoesNotConfirmAConfirmedToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class, ['confirmedOn' => 123456789]);
        $model
            ->expects($this->never())
            ->method('save')
        ;

        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The token "foobar" has already been confirmed');

        (new OptIn($framework))->confirm('foobar');
    }

    public function testSendsTheTokenViaEmail(): void
    {
        $properties = [
            'confirmedOn' => 0,
            'email' => 'foo@bar.com',
        ];

        $model = $this->mockClassWithProperties(OptInModel::class, $properties);

        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn($model)
        ;

        $email = $this->createMock(Email::class);
        $email
            ->expects($this->once())
            ->method('sendTo')
            ->with('foo@bar.com')
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(Email::class)
            ->willReturn($email)
        ;

        (new OptIn($framework))->sendMail('foobar');
    }

    public function testDoesNotSendAnInvalidTokenViaEmail(): void
    {
        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid token: foobar');

        (new OptIn($framework))->sendMail('foobar');
    }

    public function testDoesNotSendAConfirmedTokenViaEmail(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class, ['confirmedOn' => 123456789]);

        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The token "foobar" has already been confirmed');

        (new OptIn($framework))->sendMail('foobar');
    }

    public function testFlagsATokenForRemoval(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class, ['confirmedOn' => 123456789]);
        $model
            ->expects($this->once())
            ->method('save')
        ;

        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        (new OptIn($framework))->flagForRemoval('foobar', 987654321);
    }

    public function testDoesNotFlagAnInvalidTokenForRemoval(): void
    {
        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid token: foobar');

        (new OptIn($framework))->flagForRemoval('foobar', 987654321);
    }

    public function testDoesNotFlagAnUnconfirmedTokenForRemoval(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class, ['confirmedOn' => 0]);
        $model
            ->expects($this->never())
            ->method('save')
        ;

        $adapter = $this->mockAdapter(['findByToken']);
        $adapter
            ->method('findByToken')
            ->with('foobar')
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('The token "foobar" has not been confirmed yet');

        (new OptIn($framework))->flagForRemoval('foobar', 987654321);
    }

    public function testPurgesExpiredTokens(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class, ['confirmedOn' => 123456789]);
        $model
            ->expects($this->once())
            ->method('delete')
        ;

        $adapter = $this->mockAdapter(['findExpiredTokens']);
        $adapter
            ->method('findExpiredTokens')
            ->willReturn([$model])
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        (new OptIn($framework))->purgeTokens();
    }

    public function testDeletesRelatedRecords(): void
    {
        $properties = [
            'confirmedOn' => 0,
            'relatedTable' => 'tl_member',
            'relatedId' => 2,
        ];

        $optInModel = $this->mockClassWithProperties(OptInModel::class, $properties);
        $optInModel
            ->expects($this->once())
            ->method('delete')
        ;

        $optInModelAdapter = $this->mockAdapter(['findExpiredTokens']);
        $optInModelAdapter
            ->method('findExpiredTokens')
            ->willReturn([$optInModel])
        ;

        $model = $this->createMock(Model::class);
        $model
            ->expects($this->once())
            ->method('delete')
        ;

        $memberModelAdapter = $this->mockAdapter(['findByPk']);
        $memberModelAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(2)
            ->willReturn($model)
        ;

        $modelAdapter = $this->mockAdapter(['getClassFromTable']);
        $modelAdapter
            ->expects($this->once())
            ->method('getClassFromTable')
            ->willReturn(MemberModel::class)
        ;

        $adapters = [
            MemberModel::class => $memberModelAdapter,
            Model::class => $modelAdapter,
            OptInModel::class => $optInModelAdapter,
        ];

        $framework = $this->mockContaoFramework($adapters);

        (new OptIn($framework))->purgeTokens();
    }
}
