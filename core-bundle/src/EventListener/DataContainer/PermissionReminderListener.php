<?php declare(strict_types=1);

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Translation\Translator;
use Contao\DataContainer;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback('tl_form', 'config.onbeforesubmit')]
#[AsCallback('tl_files', 'config.onbeforesubmit')]
#[AsCallback('tl_image_size', 'config.onbeforesubmit')]
#[AsCallback('tl_page', 'config.onbeforesubmit')]
class PermissionReminderListener
{
    public function __construct(private RequestStack $requestStack, private Translator $translator)
    {
    }

    public function __invoke(array $values, DataContainer $dc): array
    {
        if ($dc->activeRecord->tstamp > 0) {
            return $values;
        }

        $session = $this->requestStack->getMainRequest()?->getSession();
        $session->getFlashBag()->add(
            'contao.BE.info',
            $this->translator->trans('MSC.updatePermissions', [], 'contao_default')
        );

        return $values;
    }
}
