<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Pagination;

use Twig\Environment;

/**
 * @internal
 */
final class LegacyTemplatePaginationProxy implements \Stringable
{
    public function __construct(
        private readonly Environment $twig,
        private readonly Pagination $pagination,
        private readonly string $template = '@Contao/frontend_module/pagination.html.twig',
    ) {
    }

    public function __toString(): string
    {
        return $this->twig->render($this->template, ['pagination' => $this->pagination]);
    }

    public function getObject(): Pagination
    {
        return $this->pagination;
    }
}
