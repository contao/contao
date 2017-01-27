<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps a PHP string to a binary database field.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class BinaryStringType extends Type
{
    /**
     * @var string
     */
    const NAME = 'binary_string';

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        if (isset($fieldDeclaration['fixed']) && $fieldDeclaration['fixed']) {
            return $platform->getBinaryTypeDeclarationSQL($fieldDeclaration);
        } else {
            return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
