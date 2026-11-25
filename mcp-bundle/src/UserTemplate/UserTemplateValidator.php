<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\UserTemplate;

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Mcp\Exception\ToolCallException;
use Twig\Environment;
use Twig\Error\Error;

final class UserTemplateValidator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ContaoFilesystemLoader $loader,
    ) {
    }

    public function validate(string $identifier, string $code, string|null $themeSlug): array
    {
        try {
            $logicalName = $this->loader->getFirst($identifier, $themeSlug);
        } catch (\LogicException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        try {
            $this->twig->createTemplate($code, $logicalName)->getBlockNames();
        } catch (Error $error) {
            return [
                'identifier' => $identifier,
                'valid' => false,
                'errors' => [[
                    'line' => max(1, $error->getTemplateLine()),
                    'message' => $error->getRawMessage(),
                ]],
            ];
        }

        return [
            'identifier' => $identifier,
            'valid' => true,
            'errors' => [],
        ];
    }
}
