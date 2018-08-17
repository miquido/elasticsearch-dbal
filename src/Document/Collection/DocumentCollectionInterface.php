<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Document\Collection;


use Miquido\DataStructure\HashMap\HashMapCollectionInterface;
use Miquido\Elasticsearch\Document\DocumentInterface;

interface DocumentCollectionInterface extends \Countable
{
    /**
     * @return DocumentInterface[]
     */
    public function getAll(): array;
    public function getData(): HashMapCollectionInterface;
}