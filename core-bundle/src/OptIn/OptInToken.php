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
    public function __construct(
        private OptInModel $model,
        private ContaoFramework $framework,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->model->token;
    }

    public function getEmail(): string
    {
        return $this->model->email;
    }

    public function isValid(): bool
    {
        return !$this->model->invalidatedThrough && $this->model->createdOn > strtotime('-24 hours');
    }

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

        $related = $this->model->getRelatedRecords();

        if (empty($related)) {
            return;
        }

        $adapter = $this->framework->getAdapter(OptInModel::class);
        $prefix = strtok($this->getIdentifier(), '-');

        // Invalidate other tokens that relate to the same records
        foreach ($related as $table => $ids) {
            if (!$models = $adapter->findByRelatedTableAndIds($table, $ids)) {
                continue;
            }

            foreach ($models as $model) {
                if (
                    $model->confirmedOn > 0
                    || $model->invalidatedThrough
                    || $model->token === $this->getIdentifier()
                    || 0 !== strncmp($model->token, $prefix.'-', \strlen((string) $prefix) + 1)
                ) {
                    continue;
                }

                $token = new self($model, $this->framework);

                // The related records must match exactly
                if ($token->getRelatedRecords() !== $related) {
                    continue;
                }

                $model->invalidatedThrough = $this->model->token;
                $model->save();
            }
        }
    }

    public function isConfirmed(): bool
    {
        return $this->model->confirmedOn > 0;
    }

    public function send(string|null $subject = null, string|null $text = null): void
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

        $email = $this->framework->createInstance(Email::class);
        $email->subject = $this->model->emailSubject;
        $email->text = $this->model->emailText;
        $email->from = $GLOBALS['TL_ADMIN_EMAIL'] ?? null;
        $email->fromName = $GLOBALS['TL_ADMIN_NAME'] ?? null;
        $email->sendTo($this->model->email);
    }

    public function hasBeenSent(): bool
    {
        return $this->model->emailSubject && $this->model->emailText;
    }

    public function getRelatedRecords(): array
    {
        return $this->model->getRelatedRecords();
    }
}
