<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Tests\Document\Collection;

use Miquido\DataStructure\Map\Map;
use Miquido\Elasticsearch\Document\Collection\DocumentCollection;
use Miquido\Elasticsearch\Document\Document;
use PHPUnit\Framework\TestCase;

final class DocumentCollectionTest extends TestCase
{
    public function testDocumentCollection(): void
    {
        $data1 = new Map();
        $data2 = new Map();
        $document1 = new Document(null, $data1);
        $document2 = new Document(null, $data2);
        $collection = new DocumentCollection($document1, $document2);

        $this->assertCount(2, $collection);
        $this->assertSame([$document1, $document2], $collection->getAll());
        $this->assertSame([$data1, $data2], $collection->getData()->getAll());
    }
}
