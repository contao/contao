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

use Contao\ArticleModel;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Messenger\WebWorker;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\LayoutModel;
use Contao\StringUtil;
use Imagine\Driver\InfoProvider;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Imagick\Imagine as ImagickImagine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Routing\RouterInterface;
use Toflar\CronjobSupervisor\Supervisor;

/**
 * @internal
 */
class ContaoDataCollector extends DataCollector implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    public function __construct(
        private readonly TokenChecker $tokenChecker,
        private readonly RequestStack $requestStack,
        private readonly ImagineInterface&InfoProvider $imagine,
        private readonly RouterInterface $router,
        private readonly PageFinder $pageFinder,
        private readonly Cron $cron,
        private readonly BackendSearch|null $backendSearch = null,
        private readonly WebWorker|null $webWorker = null,
    ) {
    }

    public function collect(Request $request, Response $response, \Throwable|null $exception = null): void
    {
        $this->data = ['contao_version' => ContaoCoreBundle::getVersion()];

        $this->addSummaryData();

        if ($this->requestStack->getMainRequest() === $request) {
            $this->addImageChecks();

            if ($this->backendSearch) {
                $this->addBackendSearchChecks();
            }
        }
    }

    public function getContaoVersion(): string
    {
        return $this->data['contao_version'];
    }

    /**
     * @return array<string, string|bool>
     */
    public function getSummary(): array
    {
        return $this->getData('summary');
    }

    /**
     * @return array<string, string|bool>
     */
    public function getImageChecks(): array
    {
        return $this->getData('image_checks');
    }

    /**
     * @return array<string, string|bool|array>|null
     */
    public function getBackendSearchChecks(): array|null
    {
        return $this->getData('backend_search_checks');
    }

    public function getAdditionalData(): array
    {
        $data = $this->data;

        unset(
            $data['summary'],
            $data['image_checks'],
            $data['contao_version'],
            $data['backend_search_checks'],
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
     * @return array<string, string|bool>
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
        $this->data['summary'] = [
            'version' => $this->getContaoVersion(),
            'framework' => $this->framework->isInitialized(),
            'frontend' => (bool) $this->pageFinder->getCurrentPage(),
            'preview' => $this->tokenChecker->isPreviewMode(),
            'page' => $this->getPageName(),
            'page_url' => $this->getPageUrl(),
            'layout' => $this->getLayoutName(),
            'layout_url' => $this->getLayoutUrl(),
            'articles' => $this->getArticles(),
            'template' => $this->getTemplateName(),
        ];
    }

    private function addImageChecks(): void
    {
        $this->data['image_checks'] = [
            'imagine_service' => $this->imagine::class,
            'formats' => [],
        ];

        foreach (['jpg', 'png', 'gif', 'webp', 'avif', 'heic', 'jxl'] as $imageFormat) {
            $this->data['image_checks']['formats'][$imageFormat] = $this->checkImageFormat($imageFormat);
        }

        $this->data['image_checks']['formats']['pdf'] = $this->checkPreviewFormat('pdf');
    }

    private function checkImageFormat(string $format): array
    {
        $info = [
            'label' => $format,
            'supported' => true,
            'error' => '',
        ];

        try {
            $this->imagine->create(new Box(1, 1))->get($format);
        } catch (\Throwable $exception) {
            while ($exception->getPrevious()) {
                $exception = $exception->getPrevious();
            }

            $info['supported'] = false;
            $info['error'] = $exception::class.': '.$exception->getMessage();
        }

        return $info;
    }

    private function checkPreviewFormat(string $format): array
    {
        $info = [
            'label' => $format,
            'supported' => $this->imagine->getDriverInfo()->isFormatSupported($format),
            'error' => '',
        ];

        if ($this->imagine instanceof ImagickImagine) {
            $info['supported'] = $info['supported'] || \in_array(strtoupper($format), \Imagick::queryFormats(strtoupper($format)), true);

            if ('pdf' === $format) {
                try {
                    (new \Imagick(<<<'EOF'
                        data:application/pdf,%PDF-1.0
                        1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 3 3]>>endobj
                        trailer<</Size 4/Root 1 0 R>>
                        EOF,
                    ))->getImageWidth();
                } catch (\Throwable $exception) {
                    $info['supported'] = false;
                    $info['error'] = $exception->getMessage();
                }
            }
        }

        if ($this->imagine instanceof GmagickImagine) {
            $info['supported'] = $info['supported'] || \in_array(strtoupper($format), (new \Gmagick())->queryformats(strtoupper($format)), true);
        }

        if ($this->imagine instanceof GdImagine) {
            $info['supported'] = $info['supported'] || \function_exists('image'.$format);
        }

        if (!$info['supported'] && !$info['error']) {
            $info['error'] = 'Not supported';
        }

        return $info;
    }

    private function addBackendSearchChecks(): void
    {
        $this->data['backend_search_checks'] = [
            'available' => $this->backendSearch?->isAvailable() ?? false,
            'sqlite_supported' => array_intersect(['pdo_sqlite', 'sqlite3'], array_merge(get_loaded_extensions(true), get_loaded_extensions())),
            'supervisor_supported' => Supervisor::canSuperviseWithProviders(Supervisor::getDefaultProviders()),
            'cron_running' => $this->cron->hasMinutelyCliCron(),
            'cli_workers_running' => $this->webWorker?->hasCliWorkersRunning() ?? false,
        ];
    }

    private function getPageName(): string
    {
        if (!$page = $this->pageFinder->getCurrentPage()) {
            return '';
        }

        return \sprintf('%s (ID %s)', StringUtil::decodeEntities($page->title), $page->id);
    }

    private function getPageUrl(): string
    {
        if (!$pageId = $this->pageFinder->getCurrentPage()?->id) {
            return '';
        }

        return $this->router->generate('contao_backend', ['do' => 'page', 'act' => 'edit', 'id' => $pageId]);
    }

    private function getLayoutName(): string
    {
        if (!$layout = $this->getLayout()) {
            return '';
        }

        return \sprintf('%s (ID %s)', StringUtil::decodeEntities($layout->name), $layout->id);
    }

    private function getLayoutUrl(): string
    {
        if (!$layout = $this->getLayout()) {
            return '';
        }

        return $this->router->generate('contao_backend', ['do' => 'themes', 'table' => 'tl_layout', 'act' => 'edit', 'id' => $layout->id]);
    }

    private function getArticles(): array
    {
        if (!$page = $this->pageFinder->getCurrentPage()) {
            return [];
        }

        $models = $this->framework->getAdapter(ArticleModel::class)->findByPid($page->id, ['order' => 'tl_article.sorting']) ?? [];
        $articles = [];

        foreach ($models as $article) {
            $articles[] = [
                'url' => $this->router->generate('contao_backend', ['do' => 'article', 'table' => 'tl_content', 'id' => $article->id]),
                'label' => \sprintf('%s (ID %s)', StringUtil::decodeEntities($article->title), $article->id),
            ];
        }

        return $articles;
    }

    private function getTemplateName(): string
    {
        if (!$layout = $this->getLayout()) {
            return '';
        }

        return $layout->template;
    }

    private function getLayout(): LayoutModel|null
    {
        if (!$layoutId = $this->pageFinder->getCurrentPage()?->layoutId) {
            return null;
        }

        return $this->framework->getAdapter(LayoutModel::class)->findById($layoutId);
    }
}
