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

use Contao\Model;

interface OptInTokenInterface
{
    /**
     * Returns the token identifier.
     */
    public function getIdentifier(): string;

    /**
     * Confirms the token.
     */
    public function confirm(): void;

    /**
     * Returns true if the token has been confirmed.
     */
    public function isConfirmed(): bool;

    /**
     * Sends the token via e-mail.
     */
    public function send(string $subject = null, string $text = null): void;

    /**
     * Returns true if the token has been sent via e-mail.
     */
    public function hasBeenSent(): bool;

    /**
     * Flags the token for removal.
     */
    public function flagForRemoval(int $removeOn): void;

    /**
     * Returns true if the token has been flagged for removal.
     */
    public function isFlaggedForRemoval(): bool;

    /**
     * Returns the related model.
     */
    public function getRelatedModel(): ?Model;
}
