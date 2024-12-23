<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\DependencyInjection\Attribute\AsOperationForTemplateStudioElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
#[AsOperationForTemplateStudioElement]
class SaveOperation extends AbstractOperation
{
    public function canExecute(OperationContext $context): bool
    {
        return $this->userTemplateExists($context);
    }

    public function execute(Request $request, OperationContext $context): Response|null
    {
        $this->getUserTemplatesStorage()->write(
            $context->getUserTemplatesStoragePath(),
            $request->get('code'),
        );

        return $this->render(
            '@Contao/backend/template_studio/operation/save_result.stream.html.twig',
            ['identifier' => $context->getIdentifier()],
        );
    }
}
