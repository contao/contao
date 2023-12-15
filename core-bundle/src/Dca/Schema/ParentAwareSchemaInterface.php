<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

use Contao\CoreBundle\Dca\Util\Path;

interface ParentAwareSchemaInterface
{
    public function getParent(): SchemaInterface|null;

    public function getPath(): Path;

    public function setParent(SchemaInterface|null $schema): void;

    public function getRoot(): SchemaInterface|null;
}
