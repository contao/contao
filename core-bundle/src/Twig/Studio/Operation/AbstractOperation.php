<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Finder\Finder;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\CacheInvalidator;
use Contao\CoreBundle\Twig\Studio\TemplateSkeletonFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Environment;

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

    public function success(OperationContext $context): Response
    {
        $name = $this->getName();

        return $this->render('@Contao/backend/template_studio/operation/default_result.stream.html.twig', [
            'name' => $this->getName(),
            'context' => $context,
            'translation_key' => "template_studio.message.$name.success",
            'success' => true,
        ]);
    }

    public function error(OperationContext $context, string|null $customTranslation = null): Response
    {
        $name = $this->getName();

        return $this->render('@Contao/backend/template_studio/operation/default_result.stream.html.twig', [
            'name' => $this->getName(),
            'context' => $context,
            'translation_key' => $customTranslation ?? "template_studio.message.$name.error",
            'success' => false,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['twig'] = Environment::class;
        $services['contao.twig.filesystem_loader'] = ContaoFilesystemLoader::class;
        $services['contao.filesystem.virtual.user_templates'] = VirtualFilesystemInterface::class;
        $services['contao.twig.studio.template_skeleton_factory'] = TemplateSkeletonFactory::class;
        $services['contao.twig.finder_factory'] = FinderFactory::class;
        $services['contao.twig.studio.cache_invalidator'] = CacheInvalidator::class;

        return $services;
    }

    public function getTwig(): Environment
    {
        return $this->container->get('twig');
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

    public function getTwigFinder(): Finder
    {
        return $this->container->get('contao.twig.finder_factory')->create();
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function userTemplateExists(OperationContext $context, bool $exclusive = false): bool
    {
        // Check if the first template in the chain is a custom template from the
        // Contao_Global or any theme namespace.
        $chains = $this->getContaoFilesystemLoader()->getInheritanceChains($context->getThemeSlug())[$context->getIdentifier()];
        $first = $chains[array_key_first($chains)];
        $namespace = ContaoTwigUtil::parseContaoName($first)[0] ?? '';

        $userTemplateExists = match ($context->isThemeContext()) {
            true => str_starts_with($namespace, 'Contao_Theme_') && !ContaoTwigUtil::isLegacyTemplate($first),
            false => 'Contao_User' === $namespace,
        };

        return $userTemplateExists && (!$exclusive || 1 === \count($chains));
    }

    protected function invalidateTemplateCache(OperationContext $context): void
    {
        $this->container
            ->get('contao.twig.studio.cache_invalidator')
            ->invalidateCache($context->getIdentifier(), $context->getThemeSlug())
        ;
    }

    protected function refreshTemplateHierarchy(): void
    {
        $this->getContaoFilesystemLoader()->warmUp(true);
    }
}
