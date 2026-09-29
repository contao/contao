<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForTemplateStudioElement;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[AsOperationForTemplateStudioElement]
final class SaveOperation extends AbstractOperation implements OperationDescriptionInterface
{
    public function __construct(private readonly Inspector $inspector)
    {
    }

    public function canExecute(OperationContext $context): bool
    {
        return $this->userTemplateExists($context);
    }

    public function execute(Request $request, OperationContext $context): Response
    {
        $storage = $this->getUserTemplatesStorage();
        $stateHash = $this->getStateHash($context);

        if (!$storage->fileExists($context->getUserTemplatesStoragePath())) {
            return $this->error($context);
        }

        if (null === ($code = $request->request->get('code'))) {
            throw new \LogicException('The request did not contain the template code.');
        }

        $storage->write($context->getUserTemplatesStoragePath(), $code);

        // Only invalidate the template cache of the current template
        $this->getTwig()->removeCache(
            $this->getContaoFilesystemLoader()->getFirst($context->getIdentifier(), $context->getThemeSlug()),
        );

        return $this->render('@Contao/backend/template_studio/operation/save_result.stream.html.twig', [
            'identifier' => $context->getIdentifier(),
            // In case anything changed regarding the template's relation to others, reload
            // the tab in order to update the displayed information.
            'full_reload' => $stateHash !== $this->getStateHash($context),
        ]);
    }

    private function getStateHash(OperationContext $context): string
    {
        $templateInformation = $this->inspector->inspectTemplate($context->getManagedNamespaceName());

        $state = [
            'error' => $templateInformation->getError()?->getMessage(),
        ];

        if ($templateInformation->isComponent()) {
            $state['uses'] = $templateInformation->getUses();
        } else {
            $state['extends'] = $templateInformation->getExtends();
        }

        return hash('xxh3', json_encode($state, JSON_THROW_ON_ERROR));
    }

    public function getDescription(): string
    {
        return <<<'MARKDOWN'
            Replace the complete contents of an existing user template in the selected theme context. Provide code as a
            string (for example, {"code": "{% extends '@Contao/content_element/code.html.twig' %}"}). An empty string
            empties the file; omitting code is an error. Read the template first if you want to preserve any existing
            content. If no user template exists yet, use create first. The response contains identifier and full_reload,
            indicating whether the template hierarchy information should be refreshed.
            MARKDOWN;
    }
}
