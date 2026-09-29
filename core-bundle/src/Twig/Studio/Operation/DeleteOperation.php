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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[AsOperationForTemplateStudioElement]
class DeleteOperation extends AbstractOperation implements OperationDescriptionInterface
{
    public function getDescription(): string
    {
        return <<<'MARKDOWN'
            Delete the user template in the selected theme context. To execute, provide confirm_delete (for example,
            {"confirm_delete": true}). The presence of this parameter confirms deletion, even if its value is false.
            Omit it to request confirmation information without deleting the file. For content element and frontend
            module variants, stored database selections are also reset; these selections are currently reset even on the
            confirmation request.
            MARKDOWN;
    }

    public function canExecute(OperationContext $context): bool
    {
        return $this->userTemplateExists($context);
    }

    public function execute(Request $request, OperationContext $context): Response
    {
        $storage = $this->getUserTemplatesStorage();

        if (!$storage->fileExists($context->getUserTemplatesStoragePath())) {
            return $this->error($context);
        }

        // Show a confirmation dialog
        if (!$request->request->has('confirm_delete')) {
            return $this->render('@Contao/backend/template_studio/operation/delete_confirm.stream.html.twig', [
                'identifier' => $context->getIdentifier(),
            ]);
        }

        // Delete the user template file
        $this->getUserTemplatesStorage()->delete($context->getUserTemplatesStoragePath());

        $this->invalidateTemplateCache($context);
        $this->refreshTemplateHierarchy();

        return $this->render('@Contao/backend/template_studio/operation/delete_result.stream.html.twig', [
            'identifier' => $context->getIdentifier(),
            'was_last' => !isset($this->getContaoFilesystemLoader()->getInheritanceChains()[$context->getIdentifier()]),
        ]);
    }
}
