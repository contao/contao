<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\OptIn;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Email;
use Contao\OptInModel;

class OptInToken implements OptInTokenInterface
{
    /**
     * @var OptInModel
     */
    private $model;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(OptInModel $model, ContaoFramework $framework)
    {
        $this->model = $model;
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return $this->model->token;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return $this->model->email;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return $this->model->createdOn > strtotime('-24 hours');
    }

    /**
     * {@inheritdoc}
     */
    public function confirm(): void
    {
        if ($this->isConfirmed()) {
            throw new OptInTokenAlreadyConfirmedException();
        }

        if (!$this->isValid()) {
            throw new OptInTokenNoLongerValidException();
        }

        $this->model->tstamp = time();
        $this->model->confirmedOn = time();
        $this->model->removeOn = strtotime('+3 years');
        $this->model->save();
    }

    /**
     * {@inheritdoc}
     */
    public function isConfirmed(): bool
    {
        return $this->model->confirmedOn > 0;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    public function send(string $subject = null, string $text = null): void
    {
        if ($this->isConfirmed()) {
            throw new OptInTokenAlreadyConfirmedException();
        }

        if (!$this->isValid()) {
            throw new OptInTokenNoLongerValidException();
        }

        if (!$this->hasBeenSent()) {
            if (null === $subject || null === $text) {
                throw new \LogicException('Please provide subject and text to send the token');
            }

            $this->model->emailSubject = $subject;
            $this->model->emailText = $text;
            $this->model->save();
        }

        /** @var Email $email */
        $email = $this->framework->createInstance(Email::class);
        $email->subject = $this->model->emailSubject;
        $email->text = $this->model->emailText;
        $email->sendTo($this->model->email);
    }

    /**
     * {@inheritdoc}
     */
    public function hasBeenSent(): bool
    {
        return $this->model->emailSubject && $this->model->emailText;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedRecords(): array
    {
        return $this->model->getRelatedRecords();
    }
}
