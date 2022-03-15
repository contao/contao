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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\CoreBundle\OptIn\OptInTokenAlreadyConfirmedException;
use Contao\CoreBundle\OptIn\OptInTokenInterface;
use Contao\CoreBundle\OptIn\OptInTokenNoLongerValidException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Email;
use Contao\OptInModel;

class OptInTokenTest extends TestCase
{
    public function testReturnsTheIdentifier(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->token = 'foobar';

        $token = $this->getToken($model);

        $this->assertSame('foobar', $token->getIdentifier());
    }

    public function testReturnsTheEmailAddress(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->email = 'foo@bar.com';

        $token = $this->getToken($model);

        $this->assertSame('foo@bar.com', $token->getEmail());
    }

    public function testInvalidatesAToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->invalidatedThrough = 'foo';

        $token = $this->getToken($model);

        $this->assertFalse($token->isValid());
    }

    public function testConfirmsAToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->createdOn = time();
        $model->confirmedOn = 0;

        $model
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn([])
        ;

        $token = $this->getToken($model);
        $token->confirm();

        $this->assertTrue($token->isConfirmed());
    }

    public function testInvalidatesRelatedTokens(): void
    {
        $related = $this->mockClassWithProperties(OptInModel::class);
        $related->token = 'reg-first';
        $related->createdOn = time();
        $related->confirmedOn = 0;

        $related
            ->expects($this->once())
            ->method('save')
        ;

        $related
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_user' => [2]])
        ;

        $adapter = $this->mockAdapter(['findByRelatedTableAndIds']);
        $adapter
            ->expects($this->once())
            ->method('findByRelatedTableAndIds')
            ->with('tl_user', [2])
            ->willReturn([$related])
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->token = 'reg-second';
        $model->createdOn = time();
        $model->confirmedOn = 0;

        $model
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_user' => [2]])
        ;

        $token = $this->getToken($model, $framework);
        $token->confirm();

        $this->assertTrue($token->isConfirmed());
        $this->assertSame('reg-second', $related->invalidatedThrough);
    }

    public function testDoesNotInvalidateRelatedTokensIfTheRelatedRecordsDoNotMatch(): void
    {
        $related = $this->mockClassWithProperties(OptInModel::class);
        $related->token = 'reg-first';
        $related->createdOn = time();
        $related->confirmedOn = 0;

        $related
            ->expects($this->never())
            ->method('save')
        ;

        $related
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_user' => [2, 3]])
        ;

        $adapter = $this->mockAdapter(['findByRelatedTableAndIds']);
        $adapter
            ->expects($this->once())
            ->method('findByRelatedTableAndIds')
            ->with('tl_user', [2])
            ->willReturn([$related])
        ;

        $framework = $this->mockContaoFramework([OptInModel::class => $adapter]);

        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->token = 'reg-second';
        $model->createdOn = time();
        $model->confirmedOn = 0;

        $model
            ->expects($this->once())
            ->method('getRelatedRecords')
            ->willReturn(['tl_user' => [2]])
        ;

        $token = $this->getToken($model, $framework);
        $token->confirm();

        $this->assertTrue($token->isConfirmed());
        $this->assertNull($related->invalidatedThrough);
    }

    public function testDoesNotConfirmAConfirmedToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->confirmedOn = time();

        $token = $this->getToken($model);

        $this->expectException(OptInTokenAlreadyConfirmedException::class);
        $this->expectExceptionMessage('The token has already been confirmed');

        $token->confirm();
    }

    public function testDoesNotConfirmAnExpiredToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->createdOn = strtotime('-1 day');
        $model->confirmedOn = 0;

        $token = $this->getToken($model);

        $this->expectException(OptInTokenNoLongerValidException::class);
        $this->expectExceptionMessage('The token is no longer valid');

        $token->confirm();
    }

    public function testSendsATokenViaEmail(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->createdOn = time();
        $model->confirmedOn = 0;
        $model->emailSubject = '';
        $model->emailText = '';

        $model
            ->expects($this->once())
            ->method('save')
        ;

        $email = $this->createMock(Email::class);
        $email
            ->expects($this->once())
            ->method('sendTo')
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(Email::class)
            ->willReturn($email)
        ;

        $token = $this->getToken($model, $framework);
        $token->send('Subject', 'Text');

        $this->assertTrue($token->hasBeenSent());
    }

    public function testDoesNotSendAConfirmedTokenViaEmail(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->confirmedOn = time();

        $token = $this->getToken($model);

        $this->expectException(OptInTokenAlreadyConfirmedException::class);
        $this->expectExceptionMessage('The token has already been confirmed');

        $token->send('Subject', 'Text');
    }

    public function testDoesNotSendAnExpiredTokenViaEmail(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->createdOn = strtotime('-1 day');
        $model->confirmedOn = 0;

        $token = $this->getToken($model);

        $this->expectException(OptInTokenNoLongerValidException::class);
        $this->expectExceptionMessage('The token is no longer valid');

        $token->send('Subject', 'Text');
    }

    public function testRequiresSubjectAndTextToSendToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->createdOn = time();
        $model->confirmedOn = 0;
        $model->emailSubject = '';
        $model->emailText = '';

        $model
            ->expects($this->never())
            ->method('save')
        ;

        $token = $this->getToken($model);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Please provide subject and text to send the token');

        $token->send();
    }

    public function testDoesNotRequireSubjectAndTextToResendToken(): void
    {
        $model = $this->mockClassWithProperties(OptInModel::class);
        $model->createdOn = time();
        $model->confirmedOn = 0;
        $model->emailSubject = 'Subject';
        $model->emailText = 'Text';

        $model
            ->expects($this->never())
            ->method('save')
        ;

        $email = $this->createMock(Email::class);
        $email
            ->expects($this->once())
            ->method('sendTo')
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(Email::class)
            ->willReturn($email)
        ;

        $token = $this->getToken($model, $framework);
        $token->send('Subject', 'Text');

        $this->assertTrue($token->hasBeenSent());
    }

    private function getToken(OptInModel $model, ContaoFramework $framework = null): OptInTokenInterface
    {
        return new OptInToken($model, $framework ?? $this->mockContaoFramework());
    }
}
