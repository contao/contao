<?php

namespace Contao\CoreBundle\Controller\Page;

use Contao\Config;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

abstract class AbstractPageController extends AbstractController
{
    public function __invoke(PageModel $pageModel, Request $request): Response
    {
        $this->initializeContaoFramework();
        $this->denyAccessUnlessGrantedForPage($pageModel);

        return $this->getResponse($pageModel, $request);
    }

    abstract protected function getResponse(PageModel $pageModel, Request $request): Response;

    /**
     * Throws an exception if the security user does not have access to the given page.
     *
     * @throws AccessDeniedException
     */
    protected function denyAccessUnlessGrantedForPage(PageModel $pageModel): void
    {
        if (!$pageModel->protected) {
            return;
        }

        $token = $this->get('security.token_storage')->getToken();

        if ($token instanceof AnonymousToken) {
            throw new InsufficientAuthenticationException('Not authenticated');
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser || !\in_array('ROLE_MEMBER', $token->getRoleNames(), true)) {
            throw new AccessDeniedException();
        }

        $groups = StringUtil::deserialize($pageModel->groups);
        $userGroups = StringUtil::deserialize($user->groups);

        if (
            empty($groups)
            || !\is_array($groups)
            || !\is_array($userGroups)
            || 0 === \count(array_intersect($groups, $userGroups))
        ) {
            if (null !== ($logger = $this->get('logger'))) {
                $logger->error(
                    sprintf(
                        'Page ID "%s" can only be accessed by groups "%s" (current user groups: %s)',
                        $pageModel->id,
                        implode(', ', (array) $pageModel->groups),
                        implode(', ', $token->getUser()->groups)
                    ),
                    ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
                );
            }

            throw new AccessDeniedException();
        }
    }

    protected function renderPageContent(PageModel $pageModel): Response
    {
        global $objPage;

        $objPage = $pageModel;

        $this->initializeAdminEmail((string) $pageModel->adminEmail);

        // Backup some globals (see #7659)
        $arrHead = $GLOBALS['TL_HEAD'];
        $arrBody = $GLOBALS['TL_BODY'];
        $arrMootools = $GLOBALS['TL_MOOTOOLS'];
        $arrJquery = $GLOBALS['TL_JQUERY'];

        try {
            $pageHandler = new PageRegular();

            return $pageHandler->getResponse($pageModel, true);

        } catch (\UnusedArgumentsException $e) {

            // Restore the globals (see #7659)
            $GLOBALS['TL_HEAD'] = $arrHead;
            $GLOBALS['TL_BODY'] = $arrBody;
            $GLOBALS['TL_MOOTOOLS'] = $arrMootools;
            $GLOBALS['TL_JQUERY'] = $arrJquery;

            throw $e;
        }
    }

    private function initializeAdminEmail(string $adminEmail): void
    {
        if ('' === $adminEmail) {
            /** @var Config $config */
            $config = $this->get('contao.framework')->getAdapter(Config::class);
            $adminEmail = (string) $config->get('adminEmail');
        }

        list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = StringUtil::splitFriendlyEmail($adminEmail);
    }
}
