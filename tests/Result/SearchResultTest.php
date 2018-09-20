<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Tests\Result;

use Miquido\Elasticsearch\Document\Collection\DocumentCollection;
use Miquido\Elasticsearch\Document\Document;
use Miquido\Elasticsearch\Result\SearchResult;
use PHPUnit\Framework\TestCase;

final class SearchResultTest extends TestCase
{
    public function testSearchResult(): void
    {
        $documents = new DocumentCollection(new Document(), new Document());
        $result = new SearchResult($documents, 100, 50);

        $this->assertSame($documents, $result->getDocuments());
        $this->assertCount(2, $result);
        $this->assertSame(100, $result->getTime());
        $this->assertSame(50, $result->getTotalHits());
    }
}
