<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Message;

use Contao\CoreBundle\Search\Document;

class SearchIndexMessage implements LowPriorityMessageInterface
{
    final public const ACTION_INDEX = 'index';
    final public const ACTION_DELETE = 'delete';

    private function __construct(
        private readonly Document $document,
        private readonly string $action,
    ) {
    }

    public function shouldDelete(): bool
    {
        return self::ACTION_DELETE === $this->action;
    }

    public function shouldIndex(): bool
    {
        return self::ACTION_INDEX === $this->action;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public static function createWithDelete(Document $document): self
    {
        return new self($document, self::ACTION_DELETE);
    }

    public static function createWithIndex(Document $document): self
    {
        return new self($document, self::ACTION_INDEX);
    }
}
