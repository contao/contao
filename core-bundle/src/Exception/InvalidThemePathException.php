<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Exception;

class InvalidThemePathException extends \InvalidArgumentException
{
    /**
     * @param array<string> $invalidCharacters
     */
    public function __construct(private string $path, private array $invalidCharacters)
    {
        $this->invalidCharacters = array_unique($invalidCharacters);

        parent::__construct(
            sprintf(
                'The theme path "%s" contains one or more invalid characters: "%s"',
                $path,
                implode('", "', $this->invalidCharacters),
            )
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string>
     */
    public function getInvalidCharacters(): array
    {
        return $this->invalidCharacters;
    }
}
