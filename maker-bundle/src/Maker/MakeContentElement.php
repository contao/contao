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
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\Container;

class MakeContentElement extends AbstractFragmentMaker
{
    public static function getCommandName(): string
    {
        return 'make:contao:content-element';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new content element';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('element-class', InputArgument::REQUIRED, sprintf('Enter a class name for the element controller (e.g. <fg=yellow>%sController</>)', Str::asClassName(Str::getRandomTerm())))
        ;
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $category = $input->getArgument('category');
        $addPalette = $input->getArgument('add-palette');
        $addTranslation = $input->getArgument('add-translation');
        $name = $input->getArgument('element-class');

        $className = Str::asClassName($name);
        $classNameWithoutSuffix = $this->getClassNameWithoutSuffix($className);
        $elementName = Container::underscore($classNameWithoutSuffix);
        $elementDetails = $generator->createClassNameDetails($name, 'Controller\ContentElement\\');
        $useAttributes = true;

        // Backwards compatibility with symfony/maker-bundle < 1.44.0
        if (method_exists($this->phpCompatUtil, 'canUseAttributes')) {
            $useAttributes = $this->phpCompatUtil->canUseAttributes();
        }

        $this->classGenerator->generate([
            'source' => 'content-element/ContentElement.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $elementDetails->getShortName(),
                'elementName' => $elementName,
                'category' => $category,
                'use_attributes' => $useAttributes,
            ],
        ]);

        $this->templateGenerator->generate([
            'source' => 'content-element/content_element.tpl.php',
            'target' => $this->getTemplateName($classNameWithoutSuffix),
        ]);

        if ($addPalette) {
            $this->dcaGenerator->generate([
                'source' => 'content-element/tl_content.tpl.php',
                'domain' => 'tl_content',
                'element' => $elementName,
            ]);
        }

        if ($addTranslation) {
            $this->languageFileGenerator->generate([
                'source' => 'content-element/source.tpl.php',
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
                    'source' => 'content-element/target.tpl.php',
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

    protected function getGlobalsRegistryKey(): string
    {
        return 'TL_CTE';
    }

    protected function getTemplatePrefix(): string
    {
        return 'ce';
    }
}
