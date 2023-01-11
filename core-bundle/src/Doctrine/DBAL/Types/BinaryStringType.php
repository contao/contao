<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Maps a PHP string to a binary database field.
 */
class BinaryStringType extends Type
{
    final public const NAME = 'binary_string';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (!empty($column['fixed'])) {
            return $platform->getBinaryTypeDeclarationSQL($column);
        }

        return $platform->getBlobTypeDeclarationSQL($column);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
