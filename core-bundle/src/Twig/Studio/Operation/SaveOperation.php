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
final class SaveOperation extends AbstractOperation
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

        if (null === ($code = $request->get('code'))) {
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

        return md5(json_encode($state, JSON_THROW_ON_ERROR));
    }
}
