<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Document\Collection;

use Miquido\DataStructure\Map\MapCollection;
use Miquido\DataStructure\Map\MapCollectionInterface;
use Miquido\DataStructure\Map\MapInterface;
use Miquido\Elasticsearch\Document\DocumentInterface;

final class DocumentCollection implements DocumentCollectionInterface
{
    /**
     * @var DocumentInterface[]
     */
    private $documents;

    public function __construct(DocumentInterface ...$documents)
    {
        $this->documents = $documents;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->documents);
    }

    public function getData(): MapCollectionInterface
    {
        return new MapCollection(...\array_map(
            function (DocumentInterface $document): MapInterface {
                return $document->getData();
            },
            $this->documents
        ));
    }

    /**
     * @return DocumentInterface[]
     */
    public function getAll(): array
    {
        return $this->documents;
    }
}