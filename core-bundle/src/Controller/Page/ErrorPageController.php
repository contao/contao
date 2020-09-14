<?php

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\CoreBundle\Routing\Page\PageOptionsAwareInterface;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\UriSigner;

class ErrorPageController extends AbstractPageController implements ContentCompositionInterface, PageOptionsAwareInterface
{
    /**
     * @var array
     */
    private $options = [];

    protected function getResponse(PageModel $pageModel, Request $request): Response
    {
        if (!$pageModel->autoforward || !$pageModel->jumpTo) {
            // Reset inherited cache timeouts (see #231)
            if (!$pageModel->includeCache) {
                $pageModel->cache = 0;
                $pageModel->clientCache = 0;
            }

            $response = $this->renderPageContent($pageModel);

            if (isset($this->options['status_code'])) {
                $response->setStatusCode((int) $this->options['status_code']);
            }

            return $response;
        }

        // Forward to another page

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);
        $nextPage = $pageAdapter->findPublishedById($pageModel->jumpTo);

        if (null === $nextPage) {
            if (null !== $this->get('logger')) {
                $this->get('logger')->error(
                    'Forward page ID "'.$pageModel->jumpTo.'" does not exist',
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
                );
            }

            throw new ForwardPageNotFoundException('Forward page not found');
        }

        // Add the referer so the login module can redirect back
        $url = $nextPage->getAbsoluteUrl().'?redirect='.$request->getUri();

        return $this->redirect($this->get('uri_signer')->sign($url), Response::HTTP_SEE_OTHER);
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        return !$pageModel->autoforward || !$pageModel->jumpTo;
    }

    public function setPageOptions(array $options): void
    {
        $this->options = $options;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['uri_signer'] = UriSigner::class;

        return $services;
    }
}
