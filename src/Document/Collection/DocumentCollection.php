<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Document\Collection;

use Miquido\DataStructure\HashMap\HashMapCollection;
use Miquido\DataStructure\HashMap\HashMapCollectionInterface;
use Miquido\DataStructure\HashMap\HashMapInterface;
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

    public function getData(): HashMapCollectionInterface
    {
        return new HashMapCollection(...\array_map(
            function (DocumentInterface $document): HashMapInterface {
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