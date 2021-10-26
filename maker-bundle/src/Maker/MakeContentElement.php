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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Container;

class MakeContentElement extends AbstractFragmentMaker
{
    public static function getCommandName(): string
    {
        return 'make:contao:content-element';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates an empty content element')
            ->addArgument('element', InputArgument::REQUIRED, sprintf('Choose a class name for your content element'))
        ;

        $inputConfig->setArgumentAsNonInteractive('element');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $defaultName = Str::asClassName(Str::getRandomTerm().'Controller');

        $argument = $command->getDefinition()->getArgument('element');
        $question = new Question($argument->getDescription(), $defaultName);
        $input->setArgument('element', $io->askQuestion($question));

        parent::interact($input, $io, $command);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $name = $input->getArgument('element');
        $category = $input->getArgument('category');

        $addTranslations = $input->getArgument('addTranslation');
        $addEmptyDcaPalette = $input->getArgument('addEmptyDcaPalette');

        $elementDetails = $generator->createClassNameDetails($name, 'Controller\\ContentElement\\');

        $className = Str::asClassName($name);
        $classNameWithoutSuffix = $this->getClassNameWithoutSuffix($className);
        $elementName = Container::underscore($classNameWithoutSuffix);

        $this->classGenerator->generate([
            'source' => 'content-element/ContentElement.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $className,
                'elementName' => $elementName,
                'category' => $category,
            ],
        ]);

        $this->templateGenerator->generate([
            'source' => 'content-element/content_element.tpl.html5',
            'target' => $this->getTemplateName($classNameWithoutSuffix),
        ]);

        if ($addEmptyDcaPalette) {
            $this->dcaGenerator->generate([
                'domain' => 'tl_content',
                'source' => 'content-element/tl_content.tpl.php',
                'element' => $elementName,
                'io' => $io,
            ]);
        }

        if ($addTranslations) {
            $language = 'en';
            $translatedName = $input->getArgument('translatedName');
            $translatedDescription = $input->getArgument('translatedDescription');

            $this->languageFileGenerator->generate([
                'domain' => 'default',
                'source' => 'content-element/english.tpl.xlf',
                'language' => $language,
                'io' => $io,
                'variables' => [
                    'element' => $elementName,
                    'translatedName' => $translatedName,
                    'translatedDescription' => $translatedDescription,
                ],
            ]);

            $i = 0;

            while (true) {
                $hasNext = $input->hasArgument('addAnotherTranslation_'.$i);

                if (!$hasNext || false === $input->getArgument('addAnotherTranslation_'.$i)) {
                    break;
                }

                $language = $input->getArgument('language_'.$i);
                $translatedName = $input->getArgument('translatedName_'.$i);
                $translatedDescription = $input->getArgument('translatedDescription_'.$i);

                $this->languageFileGenerator->generate([
                    'domain' => 'default',
                    'source' => 'content-element/default.tpl.xlf',
                    'language' => $language,
                    'io' => $io,
                    'variables' => [
                        'element' => $elementName,
                        'translatedName' => $translatedName,
                        'translatedDescription' => $translatedDescription,
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
