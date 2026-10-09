<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image;

use Contao\BackendUser;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\ImageSizesEvent;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageSizes implements ResetInterface
{
    private array $predefinedSizes = [];

    private array|null $options = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
    ) {
    }

    /**
     * Sets the predefined image sizes.
     */
    public function setPredefinedSizes(array $predefinedSizes): void
    {
        $this->predefinedSizes = $predefinedSizes;
    }

    /**
     * Returns the image sizes as options suitable for widgets.
     *
     * @return array<string, array<string>>
     */
    public function getAllOptions(): array
    {
        $this->loadOptions();

        $event = new ImageSizesEvent($this->options);

        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::IMAGE_SIZES_ALL);

        return $event->getImageSizes();
    }

    /**
     * Returns the image sizes for the given user suitable for widgets.
     *
     * @return array<string, array<string>>
     */
    public function getOptionsForUser(BackendUser|null $user = null): array
    {
        if ($user) {
            trigger_deprecation('contao/core-bundle', '6.1', 'Passing the user object to %s is deprecated in Contao 6.1 and will be removed in Contao 7.', __METHOD__);
        }

        $this->loadOptions();

        $event = new ImageSizesEvent($this->filterOptions($user), $user);

        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::IMAGE_SIZES_USER);

        return $event->getImageSizes();
    }

    public function reset(): void
    {
        $this->options = null;
    }

    /**
     * Loads the options from the database.
     */
    private function loadOptions(): void
    {
        if (null !== $this->options) {
            return;
        }

        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    s.id,
                    s.name,
                    s.width,
                    s.height,
                    t.name as theme
                FROM tl_image_size s
                LEFT JOIN tl_theme t ON s.pid = t.id
                ORDER BY s.pid, s.name
                SQL,
        );

        $options = [];

        foreach ($this->predefinedSizes as $name => $imageSize) {
            $options['image_sizes'][$name] = \sprintf(
                '%s (%sx%s)',
                $this->translator->trans(substr($name, 1), [], 'image_sizes') ?: substr($name, 1),
                $imageSize['width'] ?? '',
                $imageSize['height'] ?? '',
            );
        }

        foreach ($rows as $imageSize) {
            // Prefix theme names that are numeric or collide with existing group names
            if (is_numeric($imageSize['theme']) || \in_array($imageSize['theme'], ['custom', 'image_sizes', 'exact', 'relative'], true)) {
                $imageSize['theme'] = 'Theme '.$imageSize['theme'];
            }

            $options[$imageSize['theme']] ??= [];

            $options[$imageSize['theme']][$imageSize['id']] = \sprintf(
                '%s (%sx%s)',
                $imageSize['name'],
                $imageSize['width'],
                $imageSize['height'],
            );
        }

        $this->options = array_merge_recursive($options, [
            'image_sizes' => [],
            'custom' => ['crop', 'proportional', 'box'],
        ]);
    }

    /**
     * Filters the options by the given allowed sizes and returns the result.
     *
     * @return array<string, array<string>>
     */
    private function filterOptions(BackendUser|null $user): array
    {
        $filteredSizes = [];

        foreach ($this->options as $group => $sizes) {
            if ('custom' === $group || 'relative' === $group || 'exact' === $group) {
                $this->filterResizeModes($sizes, $user, $filteredSizes, $group);
            } else {
                $this->filterImageSizes($sizes, $user, $filteredSizes, $group);
            }
        }

        return $filteredSizes;
    }

    private function filterImageSizes(array $sizes, BackendUser|null $user, array &$filteredSizes, string $group): void
    {
        foreach ($sizes as $key => $size) {
            if (
                !$user
                    ? $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, $key)
                    : $this->security->isGrantedForUser($user, ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, $key)
            ) {
                $filteredSizes[$group][$key] = $size;
            }
        }
    }

    private function filterResizeModes(array $sizes, BackendUser|null $user, array &$filteredSizes, string $group): void
    {
        foreach ($sizes as $size) {
            if (
                !$user
                    ? $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, $size)
                    : $this->security->isGrantedForUser($user, ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, $size)
            ) {
                $filteredSizes[$group][] = $size;
            }
        }
    }
}
