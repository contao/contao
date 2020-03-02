<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\FrontendPreview;

use Contao\BackendUser;

interface ToolbarProviderInterface
{
    public function getName(): string;

    public function getTemplateName(): string;

    public function renderToolbarSection(BackendUser $user): ?string;
}