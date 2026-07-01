<?php

declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\Widget;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\LayoutModel;
use Contao\Model;
use Contao\PageModel;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AbstractTitleTagCallback implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    public function __invoke(Model $record): string
    {
        if (!$page = $this->getPageModel($record)) {
            return '';
        }

        $page->loadDetails();

        $layoutAdapter = $this->container->get('contao.framework')->getAdapter(LayoutModel::class);

        if (!$layout = $layoutAdapter->findById($page->layout)) {
            return '';
        }

        return match ($layout->type) {
            'default' => $this->getDefaultLayoutTitleTag($page, $layout),
            'modern' => $this->getModernLayoutTitleTag($page),
            default => throw new \LogicException(\sprintf('Unknown layout type "%s"', $layout->type)),
        };
    }

    #[Required]
    public function setContainer(ContainerInterface $container): ContainerInterface|null
    {
        $previous = $this->container ?? null;
        $this->container = $container;

        return $previous;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'contao.framework' => ContaoFramework::class,
            'contao.insert_tag.parser' => InsertTagParser::class,
        ];
    }

    abstract protected function getPageModel(Model $record): PageModel|null;

    private function getDefaultLayoutTitleTag(PageModel $page, LayoutModel $layout): string
    {
        $origObjPage = $GLOBALS['objPage'] ?? null;

        // Override the global page object, so we can replace the insert tags
        $GLOBALS['objPage'] = $page;

        $title = implode(
            '%s',
            array_map(
                fn ($titleTag): string => str_replace('%', '%%', $this->container->get('contao.insert_tag.parser')->replaceInline($titleTag)),
                explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2),
            ),
        );

        $GLOBALS['objPage'] = $origObjPage;

        return $title;
    }

    private function getModernLayoutTitleTag(PageModel $page): string
    {
        // TODO: At the moment we cannot know the exact format of the title tag in Twig
        // layouts, so this provides a sane default.
        return '%s - '.($page->rootPageTitle ?: $page->rootTitle);
    }
}
