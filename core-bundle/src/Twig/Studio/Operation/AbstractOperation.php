<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\TemplateSkeletonFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @experimental
 */
abstract class AbstractOperation extends AbstractController implements OperationInterface
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

    public function success(TemplateContext $context): Response
    {
        $name = $this->getName();

        return $this->render('@Contao/backend/template_studio/operation/default_result.stream.html.twig', [
            'name' => $this->getName(),
            'context' => $context,
            'translation_key' => "message.$name.success",
            'success' => true,
        ]);
    }

    public function error(TemplateContext $context, string|null $customTranslation = null): Response
    {
        $name = $this->getName();

        return $this->render('@Contao/backend/template_studio/operation/default_result.stream.html.twig', [
            'name' => $this->getName(),
            'context' => $context,
            'translation_key' => $customTranslation ?? "message.$name.error",
            'success' => false,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.twig.filesystem_loader'] = ContaoFilesystemLoader::class;
        $services['contao.filesystem.virtual.user_templates'] = VirtualFilesystemInterface::class;
        $services['contao.twig.studio.template_skeleton_factory'] = TemplateSkeletonFactory::class;

        return $services;
    }

    public function getContaoFilesystemLoader(): ContaoFilesystemLoader
    {
        return $this->container->get('contao.twig.filesystem_loader');
    }

    public function getUserTemplatesStorage(): VirtualFilesystemInterface
    {
        return $this->container->get('contao.filesystem.virtual.user_templates');
    }

    public function getTemplateSkeletonFactory(): TemplateSkeletonFactory
    {
        return $this->container->get('contao.twig.studio.template_skeleton_factory');
    }

    protected function getName(): string
    {
        return $this->name;
    }

    protected function refreshTemplateHierarchy(): void
    {
        $this->getContaoFilesystemLoader()->warmUp(true);
    }
}
