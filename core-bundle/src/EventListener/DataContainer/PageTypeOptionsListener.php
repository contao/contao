<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\Event\FilterPageTypeEvent;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Security;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

/**
 * @Callback(table="tl_page", target="fields.type.options")
 */
class PageTypeOptionsListener implements ServiceAnnotationInterface
{
    /**
     * @var ServiceLocator
     */
    private $contentTypes;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var EventDispatcherInterface|null
     */
    private $eventDispatcher;

    public function __construct(ServiceLocator $contentTypes, Security $security, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->contentTypes = $contentTypes;
        $this->security = $security;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(DataContainer $dc)
    {
        $pageTypes = array_keys($GLOBALS['TL_PTY']);
        $contentTypes = array_keys($this->contentTypes->getProvidedServices());

        $options = array_unique(array_merge($pageTypes, $contentTypes));

        if (null !== $this->eventDispatcher) {
            $options = $this->eventDispatcher
                ->dispatch(new FilterPageTypeEvent($options, $dc))
                ->getOptions()
            ;
        }

        // Allow the currently selected option and anything the user has access to
        foreach ($options as $k => $pageType) {
            if ($pageType !== $dc->value && !$this->security->isGranted('contao_user.alpty', $pageType)) {
                unset($options[$k]);
            }
        }

        return array_values($options);
    }
}
