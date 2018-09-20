<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Tests\Document;

use Miquido\DataStructure\Map\Map;
use Miquido\Elasticsearch\Document\Document;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function testDocument(): void
    {
        $data = new Map([
            'id' => 123,
            'name' => 'John',
        ]);
        $document1 = new Document(null, $data);
        $document2 = new Document('lorem123', $data);

        $this->assertFalse($document1->hasId());
        $this->assertSame($data, $document1->getData());

        $this->assertTrue($document2->hasId());
        $this->assertSame($data, $document2->getData());
        $this->assertSame('lorem123', $document2->getId());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Id is not set');
        $document1->getId();
    }
}
