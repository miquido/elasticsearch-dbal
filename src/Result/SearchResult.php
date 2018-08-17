<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Result;

use Miquido\Elasticsearch\Document\Collection\DocumentCollectionInterface;

final class SearchResult implements SearchResultInterface
{
    /**
     * @var DocumentCollectionInterface
     */
    private $documents;

    /**
     * @var int
     */
    private $time;

    /**
     * @var int
     */
    private $totalHits;

    public function __construct(DocumentCollectionInterface $documents, int $time, int $totalHits)
    {
        $this->documents = $documents;
        $this->time = $time;
        $this->totalHits = $totalHits;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function getTotalHits(): int
    {
        return $this->totalHits;
    }

    public function getDocuments(): DocumentCollectionInterface
    {
        return $this->documents;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->documents->count();
    }
}