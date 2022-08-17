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
            ->addArgument('module-class', InputArgument::REQUIRED, sprintf('Enter a class name for the module controller (e.g. <fg=yellow>%sController</>)', Str::asClassName(Str::getRandomTerm())))
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
        $useAttributes = true;

        // Backwards compatibility with symfony/maker-bundle < 1.44.0
        if (method_exists($this->phpCompatUtil, 'canUseAttributes')) {
            $useAttributes = $this->phpCompatUtil->canUseAttributes();
        }

        $this->classGenerator->generate([
            'source' => 'frontend-module/FrontendModule.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $elementDetails->getShortName(),
                'elementName' => $elementName,
                'category' => $category,
                'use_attributes' => $useAttributes,
            ],
        ]);

        $this->templateGenerator->generate([
            'source' => 'frontend-module/frontend_module.tpl.php',
            'target' => $this->getTemplateName($classNameWithoutSuffix),
        ]);

        if ($addPalette) {
            $this->dcaGenerator->generate([
                'source' => 'frontend-module/tl_module.tpl.php',
                'domain' => 'tl_module',
                'element' => $elementName,
            ]);
        }

        if ($addTranslation) {
            $this->languageFileGenerator->generate([
                'source' => 'frontend-module/source.tpl.php',
                'domain' => 'default',
                'language' => 'en',
                'variables' => [
                    'element' => $elementName,
                    'sourceName' => $input->getArgument('source-name'),
                    'sourceDescription' => $input->getArgument('source-description'),
                ],
            ]);

            $i = 0;

            while (true) {
                $hasNext = $input->hasArgument('add-translation-'.$i);

                if (!$hasNext || false === $input->getArgument('add-translation-'.$i)) {
                    break;
                }

                $this->languageFileGenerator->generate([
                    'source' => 'frontend-module/target.tpl.php',
                    'domain' => 'default',
                    'language' => $input->getArgument('language-'.$i),
                    'variables' => [
                        'element' => $elementName,
                        'sourceName' => $input->getArgument('source-name'),
                        'sourceDescription' => $input->getArgument('source-description'),
                        'translatedName' => $input->getArgument('target-name-'.$i),
                        'translatedDescription' => $input->getArgument('target-description-'.$i),
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
        return 'mod';
    }
}
