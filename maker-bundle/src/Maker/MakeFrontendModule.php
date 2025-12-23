<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Path;

class MakeFrontendModule extends AbstractFragmentMaker
{
    public static function getCommandName(): string
    {
        return 'make:contao:frontend-module';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new front end module';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('module-class', InputArgument::REQUIRED, \sprintf('Enter a class name for the module controller (e.g. <fg=yellow>%sController</>)', Str::asClassName(Str::getRandomTerm())))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $category = $input->getArgument('category');
        $addPalette = $input->getArgument('add-palette');
        $addTranslation = $input->getArgument('add-translation');
        $name = $input->getArgument('module-class');

        $className = Str::asClassName($name);
        $classNameWithoutSuffix = $this->getClassNameWithoutSuffix($className);
        $elementName = Container::underscore($classNameWithoutSuffix);
        $elementDetails = $generator->createClassNameDetails($name, 'Controller\\FrontendModule\\');

        $this->classGenerator->generate([
            'source' => 'frontend-module/FrontendModule.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $elementDetails->getShortName(),
                'elementName' => $elementName,
                'category' => $category,
            ],
        ]);

        $this->templateGenerator->generate([
            'source' => 'frontend-module/frontend_module.tpl.html.twig',
            'target' => $this->getTemplateName($classNameWithoutSuffix),
        ]);

        $twigRoot = Path::join($this->projectDir, 'contao/templates/.twig-root');

        if (!$this->fileManager->fileExists($twigRoot)) {
            $this->fileManager->dumpFile($twigRoot, '');
        }

        if ($addPalette) {
            $this->dcaGenerator->generate([
                'source' => 'frontend-module/tl_module.tpl.php',
                'domain' => 'tl_module',
                'element' => $elementName,
            ]);
        }

        if ($addTranslation) {
            $this->languageFileGenerator->generate([
                'domain' => 'contao_modules',
                'language' => 'en',
                'variables' => [
                    'FMD' => [
                        $elementName => [
                            $input->getArgument('source-name'),
                            $input->getArgument('source-description'),
                        ],
                    ],
                ],
            ]);

            $i = 0;

            while (true) {
                $hasNext = $input->hasArgument('add-translation-'.$i);

                if (!$hasNext || false === $input->getArgument('add-translation-'.$i)) {
                    break;
                }

                $this->languageFileGenerator->generate([
                    'domain' => 'contao_modules',
                    'language' => $input->getArgument('language-'.$i),
                    'variables' => [
                        'FMD' => [
                            $elementName => [
                                $input->getArgument('target-name-'.$i),
                                $input->getArgument('target-description-'.$i),
                            ],
                        ],
                    ],
                ]);

                ++$i;
            }
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    protected function getGlobalsRegistryKey(): string
    {
        return 'FE_MOD';
    }

    protected function getTemplatePrefix(): string
    {
        return 'frontend_module';
    }
}
