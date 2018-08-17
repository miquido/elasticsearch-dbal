<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch;

use Miquido\Elasticsearch\Document\DocumentInterface;
use Miquido\Elasticsearch\Exception\DocumentNotFoundException;
use Miquido\Elasticsearch\Result\SearchResultInterface;
use Elastica;

interface DBALInterface
{
    public function count(Elastica\Query $query): int;
    public function countAll(): int;

    public function search(Elastica\Query $query): SearchResultInterface;
    public function searchAll(Elastica\Query $query, $scrollExpiryTime = '1m'): SearchResultInterface;
    public function findByIds(string ...$ids): SearchResultInterface;

    /**
     * @param Elastica\Query $query
     *
     * @throws DocumentNotFoundException
     *
     * @return DocumentInterface
     */
    public function findOne(Elastica\Query $query): DocumentInterface;

    public function updateByQuery(Elastica\Query $query, Elastica\Script\Script $script, string $scriptKey = 'source'): void;

    public function updatePatch(DocumentInterface $document): void;
    public function bulkUpdatePatch(DocumentInterface ...$documents): void;

    public function add(DocumentInterface $document): void;
    public function bulkAdd(DocumentInterface ...$documents): void;

    public function deleteByIds(string ...$ids): void;

    public function executeQuery(Elastica\Query $query): Elastica\ResultSet;
}