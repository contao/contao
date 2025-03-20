<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem\FileManager\Operation;

use Contao\CoreBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @experimental
 */
abstract class AbstractElementsOperation extends AbstractController implements ElementsOperationInterface
{
    private string|null $name = null;

    /**
     * @internal
     */
    #[Required]
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function success(ElementsOperationContext $context): Response
    {
        $name = $this->getName();

        return $this->render('@Contao/backend/file_manager/operation/default_result.stream.html.twig', [
            'name' => $this->getName(),
            'context' => $context,
            'translation_key' => "message.$name.success",
            'success' => true,
        ]);
    }

    public function error(ElementsOperationContext $context, string|null $customTranslation = null, int|null $affectedElements = null): Response
    {
        $name = $this->getName();

        return $this->render('@Contao/backend/file_manager/operation/default_result.stream.html.twig', [
            'name' => $this->getName(),
            'context' => $context,
            'translation_key' => $customTranslation ?? "message.$name.error",
            'success' => false,
            'num_elements' => $affectedElements ?? $context->getFilesystemItems()->count(),
        ]);
    }

    protected function getName(): string
    {
        return $this->name;
    }
}
