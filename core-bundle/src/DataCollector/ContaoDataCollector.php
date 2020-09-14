<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataCollector;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Util\PackageUtil;
use Contao\LayoutModel;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class ContaoDataCollector extends DataCollector implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var bool
     */
    private $legacyRouting;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * @var string
     */
    private $urlSuffix;

    public function __construct(bool $legacyRouting, string $projectDir, bool $prependLocale, string $urlSuffix)
    {
        $this->legacyRouting = $legacyRouting;
        $this->projectDir = $projectDir;
        $this->prependLocale = $prependLocale;
        $this->urlSuffix = $urlSuffix;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
        $this->data = ['contao_version' => PackageUtil::getContaoVersion()];

        $this->addSummaryData();
        $this->addLegacyRoutingData();

        if (isset($GLOBALS['TL_DEBUG'])) {
            $this->data = array_merge($this->data, $GLOBALS['TL_DEBUG']);
        }
    }

    public function getContaoVersion(): string
    {
        return $this->data['contao_version'];
    }

    /**
     * @return array<string,string|bool>
     */
    public function getSummary(): array
    {
        return $this->getData('summary');
    }

    /**
     * @return array<string|bool>
     */
    public function getClassesSet(): array
    {
        $data = $this->getData('classes_set');

        sort($data);

        return $data;
    }

    /**
     * @return array<string|bool>
     */
    public function getClassesAliased(): array
    {
        $data = $this->getData('classes_aliased');

        ksort($data);

        return $data;
    }

    /**
     * @return array<string|bool>
     */
    public function getClassesComposerized(): array
    {
        $data = $this->getData('classes_composerized');

        ksort($data);

        return $data;
    }

    public function getLegacyRouting(): array
    {
        return $this->getData('legacy_routing');
    }

    /**
     * @return array<mixed>
     */
    public function getAdditionalData(): array
    {
        $data = $this->data;

        unset(
            $data['summary'],
            $data['contao_version'],
            $data['classes_set'],
            $data['classes_aliased'],
            $data['classes_composerized'],
            $data['database_queries'],
            $data['legacy_routing']
        );

        return $data;
    }

    public function getName(): string
    {
        return 'contao';
    }

    public function reset(): void
    {
        $this->data = [];
    }

    /**
     * @return array<string,string|bool>
     */
    private function getData(string $key): array
    {
        if (!isset($this->data[$key]) || !\is_array($this->data[$key])) {
            return [];
        }

        return $this->data[$key];
    }

    private function addSummaryData(): void
    {
        $framework = false;
        $modelCount = 0;

        if (isset($GLOBALS['TL_DEBUG'])) {
            $framework = true;
            $modelCount = Registry::getInstance()->count();
        }

        $this->data['summary'] = [
            'version' => $this->getContaoVersion(),
            'framework' => $framework,
            'models' => $modelCount,
            'frontend' => isset($GLOBALS['objPage']),
            'preview' => \defined('BE_USER_LOGGED_IN') && true === BE_USER_LOGGED_IN,
            'layout' => $this->getLayoutName(),
            'template' => $this->getTemplateName(),
            'legacy_routing' => $this->legacyRouting,
        ];
    }

    private function addLegacyRoutingData(): void
    {
        $hooks = [];

        foreach (['getPageIdFromUrl', 'getRootPageFromUrl'] as $name) {
            if (empty($GLOBALS['TL_HOOKS'][$name]) || !\is_array($GLOBALS['TL_HOOKS'][$name])) {
                continue;
            }

            /** @var System $systemAdapter */
            $systemAdapter = $this->framework->getAdapter(System::class);

            foreach ($GLOBALS['TL_HOOKS'][$name] as $callback) {
                $class = $systemAdapter->importStatic($callback[0]);
                $file = (new \ReflectionClass($class))->getFileName();
                $vendorDir = $this->projectDir.'/vendor/';

                $hook = [
                    'name' => $name,
                    'class' => \get_class($class),
                    'method' => $callback[1],
                    'package' => '',
                ];

                if (Path::isBasePath($vendorDir, $file)) {
                    [$vendor, $package] = explode('/', Path::makeRelative($file, $vendorDir), 3);
                    $hook['package'] = $vendor.'/'.$package;
                }

                $hooks[] = $hook;
            }
        }

        $this->data['legacy_routing'] = [
            'enabled' => $this->legacyRouting,
            'prepend_locale' => $this->prependLocale,
            'url_suffix' => $this->urlSuffix,
            'hooks' => $hooks,
        ];
    }

    private function getLayoutName(): string
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return sprintf('%s (ID %s)', StringUtil::decodeEntities($layout->name), $layout->id);
    }

    private function getTemplateName(): string
    {
        $layout = $this->getLayout();

        if (null === $layout) {
            return '';
        }

        return $layout->template;
    }

    private function getLayout(): ?LayoutModel
    {
        if (!isset($GLOBALS['objPage'])) {
            return null;
        }

        /** @var PageModel $objPage */
        $objPage = $GLOBALS['objPage'];

        /** @var LayoutModel $layout */
        $layout = $this->framework->getAdapter(LayoutModel::class);

        return $layout->findByPk($objPage->layoutId);
    }
}
