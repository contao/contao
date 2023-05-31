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

interface OptInTokenInterface
{
    /**
     * Returns the token identifier.
     */
    public function getIdentifier(): string;

    /**
     * Returns the e-mail address.
     */
    public function getEmail(): string;

    /**
     * Returns true if the token is valid.
     */
    public function isValid(): bool;

    /**
     * Confirms the token.
     *
     * @throws OptInTokenAlreadyConfirmedException
     * @throws OptInTokenNoLongerValidException
     */
    public function confirm(): void;

    /**
     * Returns true if the token has been confirmed.
     */
    public function isConfirmed(): bool;

    /**
     * Sends the token via e-mail.
     *
     * @throws OptInTokenAlreadyConfirmedException
     * @throws OptInTokenNoLongerValidException
     */
    public function send(string|null $subject = null, string|null $text = null): void;

    /**
     * Returns true if the token has been sent via e-mail.
     */
    public function hasBeenSent(): bool;

    /**
     * Returns the related records.
     */
    public function getRelatedRecords(): array;
}
