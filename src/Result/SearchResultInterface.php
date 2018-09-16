<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Result;

use Miquido\Elasticsearch\Document\Collection\DocumentCollectionInterface;

interface SearchResultInterface extends \Countable
{
    public function getTime(): int;
    public function getTotalHits(): int;
    public function getDocuments(): DocumentCollectionInterface;
}