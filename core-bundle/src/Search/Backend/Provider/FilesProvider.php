<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend\Provider;

use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\IndexUpdateConfigInterface;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\UpdateAllProvidersConfig;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @experimental
 */
class FilesProvider implements ProviderInterface
{
    public const TYPE = 'file';

    public function __construct(
        private readonly Connection $connection,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsType(string $type): bool
    {
        return self::TYPE === $type;
    }

    /**
     * @return iterable<Document>
     */
    public function updateIndex(IndexUpdateConfigInterface $trigger): iterable
    {
        if (!$trigger instanceof UpdateAllProvidersConfig) {
            return new \EmptyIterator();
        }

        $qb = $this->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('tl_files')
        ;

        if ($trigger->getUpdateSince()) {
            $qb->andWhere('tstamp <= ', $qb->createNamedParameter($trigger->getUpdateSince()));
        }

        foreach ($qb->executeQuery()->iterateAssociative() as $row) {
            $document = new Document(
                (string) $row['id'],
                self::TYPE,
                $row['name'] ?? '',
            );

            // Use the entire row as metadata. In the core, we use at least "path" for
            // permission checks and "name" in convertDocumentToHit() but third-party
            // developers might want to use the rest of the data for more logic.
            yield $document
                ->withTags(['extension:'.$row['extension']])
                ->withMetadata($row)
            ;
        }
    }

    public function convertDocumentToHit(Document $document): Hit
    {
        // TODO: service for view and edit URLs
        $viewUrl = 'https://todo.com?view='.$document->getId();
        $editUrl = 'https://todo.com?edit='.$document->getId();

        return (new Hit($document->getMetadata()['name'], $viewUrl))
            ->withEditUrl($editUrl)
            ->withContext($document->getSearchableContent())
            ->withImage($document->getMetadata()['path'] ?? null)
        ;
    }

    public function canAccessDocument(TokenInterface $token, Document $document): bool
    {
        $path = $document->getMetadata()['path'] ?? '';

        return $this->accessDecisionManager->decide($token, ['contao_user.filemounts'], $path);
    }
}
