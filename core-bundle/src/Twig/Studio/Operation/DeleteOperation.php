<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForTemplateStudioElement;
use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
#[AsOperationForTemplateStudioElement]
class DeleteOperation extends AbstractOperation
{
    public function canExecute(TemplateContext $context): bool
    {
        // Check if the first template in the chain is a custom template from the
        // Contao_Global namespace.
        $first = $this->getContaoFilesystemLoader()->getFirst($context->getIdentifier());

        return 'Contao_Global' === (ContaoTwigUtil::parseContaoName($first)[0] ?? '');
    }

    public function execute(Request $request, TemplateContext $context): Response|null
    {
        $storage = $this->getUserTemplatesStorage();

        if (!$storage->fileExists($context->getUserTemplatesStoragePath())) {
            return $this->error($context);
        }

        // Delete the user template file
        $this->getUserTemplatesStorage()->delete($context->getUserTemplatesStoragePath());

        $this->refreshTemplateHierarchy();

        return $this->render('@Contao/backend/template_studio/operation/delete_result.stream.html.twig', [
            'identifier' => $context->getIdentifier(),
            'was_last' => !isset($this->getContaoFilesystemLoader()->getInheritanceChains()[$context->getIdentifier()]),
        ]);
    }
}
