<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Form;

use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\Form\DTO\OverwriteTemplateDTO;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Webmozart\PathUtil\Path;

class BackendOverwriteTemplateType extends AbstractType
{
    /**
     * @var ResourceFinderInterface
     */
    private $resourceFinder;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ResourceFinderInterface $resourceFinder, string $projectDir, Environment $twig, TranslatorInterface $translator)
    {
        $this->resourceFinder = $resourceFinder;
        $this->projectDir = $projectDir;
        $this->twig = $twig;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $getLabel = function (string $id): ?string {
            return $this->translator->trans("$id.0", [], 'contao_default');
        };

        $getHelp = function (string $id) use ($options): ?string {
            if (!$options['showHelp']) {
                return null;
            }

            return $this->translator->trans("$id.1", [], 'contao_default');
        };

        $bundleTargetPathMapping = [];

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Contao Templates' => OverwriteTemplateDTO::TYPE_CONTAO_TEMPLATE,
                    'Bundle Templates' => OverwriteTemplateDTO::TYPE_BUNDLE_TEMPLATE,
                ],
                'expanded' => true,
                'label' => $getLabel('tl_templates.type'),
                'help' => $getHelp('tl_templates.type'),
            ])
            ->add('sourceContao', ChoiceType::class, [
                'choices' => $this->getContaoTemplates(),
                'required' => false,
                'label' => $getLabel('tl_templates.original'),
                'help' => $getHelp('tl_templates.original'),
            ])
            ->add('targetDirectory', ChoiceType::class, [
                'choices' => $this->getTargetDirectories(),
                'label' => $getLabel('tl_templates.target'),
                'help' => $getHelp('tl_templates.target'),
            ])
            ->add('sourceBundle', ChoiceType::class, [
                'choices' => $this->getBundleTemplates($bundleTargetPathMapping),
                'required' => false,
                'label' => $getLabel('tl_templates.bundle'),
                'help' => $getHelp('tl_templates.bundle'),
            ])
        ;

        // Set target path mapping for bundle templates.
        $data = $builder->getData() ?? new OverwriteTemplateDTO();
        $data->setBundleTargetPathMapping($bundleTargetPathMapping);

        $builder->setData($data);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OverwriteTemplateDTO::class,
            'showHelp' => false,
            'validation_groups' => static function (FormInterface $form): array {
                /** @var OverwriteTemplateDTO $data */
                $data = $form->getData();

                return array_filter(['Default', $data->getType()]);
            },
        ]);
    }

    /**
     * Get a list of target directory choices for Contao templates inside the
     * application's template folder.
     *
     * @return array<string, string>
     */
    private function getTargetDirectories(): array
    {
        $directories = (new Finder())
            ->directories()
            ->in($this->projectDir)
            ->path('/^templates/')
            ->notPath('/^templates\/bundles/')
        ;

        $options = [];

        foreach ($directories as $directory) {
            $path = Path::normalize($directory->getPathname());
            $relativePath = Path::normalize($directory->getRelativePathname());

            $options[$relativePath] = $path;
        }

        return $options;
    }

    /**
     * Get a list of Contao template choices, grouped by vendor/module.
     *
     * @return array<string, array<string, string>
     */
    private function getContaoTemplates(): array
    {
        $files = $this->resourceFinder
            ->findIn('templates')
            ->files()
            ->name('/\.html5$/')
        ;

        $getGroupName = function (string $path): string {
            if (!Path::isBasePath($this->projectDir, $path)) {
                return Path::getDirectory($path);
            }

            // Try to identify 'vendor' or 'system/modules' path
            return preg_replace(
                '@^(vendor/([^/]+/[^/]+)/|system/modules/([^/]+)/).*$@',
                '$2$3',
                Path::makeRelative($path, $this->projectDir)
            );
        };

        $groups = [];

        foreach ($files as $file) {
            $path = Path::normalize($file->getPathname());

            $groups[$getGroupName($path)][$file->getFilename()] = $path;
        }

        return $groups;
    }

    /**
     * Get a list of bundle template choices, grouped by namespace.
     *
     * @param array<string,string> $targetPathMapping
     *
     * @return array<string, array<string, string>
     */
    private function getBundleTemplates(array &$targetPathMapping): array
    {
        $loader = $this->twig->getLoader();

        if (!$loader instanceof FilesystemLoader) {
            return [];
        }

        $groups = [];

        foreach ($loader->getNamespaces() as $namespace) {
            if (0 === strpos($namespace, '!')) {
                continue;
            }

            $paths = array_filter(
                $loader->getPaths($namespace),
                static function (string $path): bool {
                    return 1 === preg_match('@/views$@', $path);
                }
            );

            if (empty($paths)) {
                continue;
            }

            $templates = (new Finder())
                ->in($paths)
                ->files()
                ->name('/\.twig/')
            ;

            if (!$templates->hasResults()) {
                continue;
            }

            // Group templates under namespace and shorten display name
            $options = [];
            $basePath = Path::getLongestCommonBasePath($paths);

            foreach ($templates as $template) {
                $path = Path::normalize($template->getPathname());

                $relativeBundlePath = Path::makeRelative($path, $basePath);

                if ('__main__' === $namespace) {
                    $targetPathMapping[$path] = Path::join(
                        $this->projectDir,
                        'templates',
                        $relativeBundlePath
                    );
                } else {
                    $targetPathMapping[$path] = Path::join(
                        $this->projectDir,
                        'templates/bundles',
                        "{$namespace}Bundle",
                        $relativeBundlePath
                    );
                }

                $options[$relativeBundlePath] = $path;
            }

            $groups['__main__' === $namespace ? '(Main)' : $namespace] = $options;
        }

        return $groups;
    }
}
