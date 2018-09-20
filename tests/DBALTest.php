<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Tests;

use Elastica;
use Miquido\DataStructure\Map\Map;
use Miquido\Elasticsearch\DBAL;
use Miquido\Elasticsearch\Exception\DocumentNotFoundException;
use Miquido\Elasticsearch\Exception\ElasticsearchQueryException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class DBALTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCount(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('isOk')->once()->andReturn(true);

        $resultSetMock = \Mockery::mock(Elastica\ResultSet::class);
        $resultSetMock->shouldReceive('getResponse')->once()->andReturn($responseMock);
        $resultSetMock->shouldReceive('getResults')->once()->andReturn([]);
        $resultSetMock->shouldReceive('getTotalTime')->once()->andReturn(10);
        $resultSetMock->shouldReceive('getTotalHits')->once()->andReturn(12345);

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('search')->once()->withArgs(function (Elastica\Query $query): bool {
            $this->assertSame(0, $query->getParam('size'));
            $this->assertSame('{"match":{"field":"value"}}', \json_encode($query->getQuery()));

            return true;
        })->andReturn($resultSetMock);

        $query = new Elastica\Query(new Elastica\Query\Match('field', 'value'));
        $dbal = new DBAL($typeMock);
        $this->assertSame(12345, $dbal->count($query));
    }

    public function testCountAll(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('isOk')->once()->andReturn(true);

        $resultSetMock = \Mockery::mock(Elastica\ResultSet::class);
        $resultSetMock->shouldReceive('getResponse')->once()->andReturn($responseMock);
        $resultSetMock->shouldReceive('getResults')->once()->andReturn([]);
        $resultSetMock->shouldReceive('getTotalTime')->once()->andReturn(10);
        $resultSetMock->shouldReceive('getTotalHits')->once()->andReturn(12345);

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('search')->once()->withArgs(function (Elastica\Query $query): bool {
            $this->assertSame(0, $query->getParam('size'));
            $this->assertInstanceOf(Elastica\Query\MatchAll::class, $query->getQuery());

            return true;
        })->andReturn($resultSetMock);

        $dbal = new DBAL($typeMock);
        $this->assertSame(12345, $dbal->countAll());
    }

    public function testFindOne(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('isOk')->once()->andReturn(true);

        $resultSetMock = \Mockery::mock(Elastica\ResultSet::class);
        $resultSetMock->shouldReceive('getResponse')->once()->andReturn($responseMock);
        $resultSetMock->shouldReceive('getResults')->once()->andReturn([new Elastica\Result([
            '_id' => '1234',
            '_source' => ['name' => 'John Smith'],
        ])]);
        $resultSetMock->shouldReceive('getTotalTime')->once()->andReturn(10);
        $resultSetMock->shouldReceive('getTotalHits')->once()->andReturn(12345);

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('search')->andReturn($resultSetMock);

        $query = new Elastica\Query(new Elastica\Query\Match('field', 'value'));
        $dbal = new DBAL($typeMock);

        $document = $dbal->findOne($query);
        $this->assertSame('1234', $document->getId());
        $this->assertEquals(new Map(['name' => 'John Smith']), $document->getData());
    }

    public function testFindOne_NoResults(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('isOk')->once()->andReturn(true);

        $resultSetMock = \Mockery::mock(Elastica\ResultSet::class);
        $resultSetMock->shouldReceive('getResponse')->once()->andReturn($responseMock);
        $resultSetMock->shouldReceive('getResults')->once()->andReturn([]);
        $resultSetMock->shouldReceive('getTotalTime')->once()->andReturn(10);
        $resultSetMock->shouldReceive('getTotalHits')->once()->andReturn(0);

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('search')->andReturn($resultSetMock);

        $dbal = new DBAL($typeMock);

        $this->expectException(DocumentNotFoundException::class);
        $this->expectExceptionMessage('Document not found');
        $dbal->findOne(new Elastica\Query(new Elastica\Query\Match('field', 'value')));
    }

    public function testFindByIds(): void
    {
//        $responseMock = \Mockery::mock(Elastica\Response::class);
//        $responseMock->shouldReceive('isOk')->once()->andReturn(true);
//
        $resultSetMock = \Mockery::mock(Elastica\ResultSet::class);
//        $resultSetMock->shouldReceive('getResponse')->once()->andReturn($responseMock);
        $resultSetMock->shouldReceive('getResults')->once()->andReturn([
            new Elastica\Result([
                '_id' => 'id1',
                '_source' => ['name' => 'John Smith'],
            ]),
            new Elastica\Result([
                '_id' => 'id3',
                '_source' => ['name' => 'John Doe'],
            ]),
        ]);
        $resultSetMock->shouldReceive('getTotalTime')->once()->andReturn(10);
//        $resultSetMock->shouldReceive('getTotalHits')->once()->andReturn(12345);

        $searchMock = \Mockery::mock(Elastica\Search::class);
        $searchMock->shouldReceive('scroll')->once()->andReturn(['scrollId' => $resultSetMock]);

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('createSearch')->once()->andReturn($searchMock);
//        $typeMock->shouldReceive('search')->once()->withArgs(function (Elastica\Query $query): bool {
//            $this->assertSame(0, $query->getParam('size'));
//            $this->assertSame('{"match":{"field":"value"}}', \json_encode($query->getQuery()));
//
//            return true;
//        })->andReturn($resultSetMock);

        $dbal = new DBAL($typeMock);
        $result = $dbal->findByIds('id1', 'id2', 'id3');
        $this->assertCount(2, $result);
        $this->assertSame(2, $result->getTotalHits());
        $documents = $result->getDocuments();
        $this->assertCount(2, $documents);

        foreach ($documents->getAll() as $document) {
            $this->assertContains($document->getId(), ['id1', 'id3']);
            $this->assertTrue($document->getData()->has('name'));
            $this->assertContains($document->getData()->get('name'), ['John Smith', 'John Doe']);
        }
    }

    public function testFindByIds_NoIdsProvided(): void
    {
        $typeMock = \Mockery::mock(Elastica\Type::class);

        $dbal = new DBAL($typeMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide at least one id');
        $dbal->findByIds();
    }

    public function testNotOkResponse(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('isOk')->once()->andReturn(false);
        $responseMock->shouldReceive('getStatus')->once()->andReturn(401);

        $resultSetMock = \Mockery::mock(Elastica\ResultSet::class);
        $resultSetMock->shouldReceive('getResponse')->once()->andReturn($responseMock);

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('search')->andReturn($resultSetMock);

        $query = new Elastica\Query(new Elastica\Query\Match('field', 'value'));
        $dbal = new DBAL($typeMock);

        $this->expectException(ElasticsearchQueryException::class);
        $this->expectExceptionMessage('Elastic query failed (status: 401)');
        $dbal->search($query);
    }
}
