<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Slots;

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @experimental
 */
final class SlotTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $nameToken = $this->parser->getStream()->expect(Token::NAME_TYPE, null, '');

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new SlotNode($nameToken->getValue(), $token->getLine());
    }

    public function getTag(): string
    {
        return 'slot';
    }
}
