<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Runtime;

use Highlight\HighlightResult as BaseHighlightResult;

/**
 * This class is a thin wrapper around @see \Highlight\HighlightResult that
 * provides an additional __toString() function.
 */
class HighlightResult extends BaseHighlightResult implements \Stringable
{
    /**
     * @internal
     */
    public function __construct(BaseHighlightResult|\stdClass $result)
    {
        foreach (get_object_vars($result) as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
