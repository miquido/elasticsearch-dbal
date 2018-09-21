<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Tests;

use Elastica;
use Miquido\DataStructure\Map\Map;
use Miquido\Elasticsearch\DBAL;
use Miquido\Elasticsearch\Document\Document;
use Miquido\Elasticsearch\Exception\DocumentNotFoundException;
use Miquido\Elasticsearch\Exception\ElasticsearchQueryException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

final class DBALTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use MatchesSnapshots;

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
            $this->assertMatchesJsonSnapshot(\json_encode($query->toArray()));

            return true;
        })->andReturn($resultSetMock);

        $dbal = new DBAL($typeMock);
        $this->assertSame(12345, $dbal->countAll());
    }

    public function testSearchAll_InvalidFromParam(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please do not use from/size with searchAll');

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $dbal = new DBAL($typeMock);

        $query = new Elastica\Query(new Elastica\Query\MatchAll());
        $query->setFrom(1);

        $dbal->searchAll($query);
    }

    public function testSearchAll_InvalidSizeParam(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please do not use from/size with searchAll');

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $dbal = new DBAL($typeMock);

        $query = new Elastica\Query(new Elastica\Query\MatchAll());
        $query->setSize(1);

        $dbal->searchAll($query);
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
        $resultSetMock = \Mockery::mock(Elastica\ResultSet::class);
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

        $searchMock = \Mockery::mock(Elastica\Search::class);
        $searchMock->shouldReceive('scroll')->once()->andReturn(['scrollId' => $resultSetMock]);

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('createSearch')->once()->withArgs(function (Elastica\Query $query): bool {
            $this->assertMatchesJsonSnapshot(\json_encode($query->toArray()));

            return true;
        })->andReturn($searchMock);

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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Please provide at least one id');

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $dbal = new DBAL($typeMock);
        $dbal->findByIds();
    }

    public function testUpdatePatch(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('getData')->andReturn([]);

        $clientMock = \Mockery::mock(Elastica\Client::class);
        $clientMock->shouldReceive('request')->withArgs(function (): bool {
            $this->assertMatchesJsonSnapshot(\json_encode(\func_get_args()));

            return true;
        })->once()->andReturn($responseMock);

        $indexMock = \Mockery::mock(Elastica\Index::class);
        $indexMock->shouldReceive('getName')->once()->andReturn('test_index');
        $indexMock->shouldReceive('getClient')->once()->andReturn($clientMock);
        $indexMock->shouldReceive('refresh')->once();

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('getName')->once()->andReturn('test_type');
        $typeMock->shouldReceive('getIndex')->andReturn($indexMock);

        $dbal = new DBAL($typeMock);
        $dbal->updatePatch(new Document('id', new Map([
            'name' => 'John',
            'surname' => 'Smith',
        ])));
    }

    public function testUpdateByQuery(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('isOk')->once()->andReturn(true);

        $indexMock = \Mockery::mock(Elastica\Index::class);
        $indexMock->shouldReceive('refresh')->once();

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('getIndex')->once()->andReturn($indexMock);
        $typeMock->shouldReceive('request')->once()->withArgs(function () {
            $this->assertMatchesJsonSnapshot(\json_encode(\func_get_args()));

            return true;
        })->andReturn($responseMock);

        $query = new Elastica\Query(new Elastica\Query\Terms('field_name', [1, 2, 3]));
        $script = new Elastica\Script\Script('ctx._source.user = params.user', [
            'user' => [
                'name' => 'John',
                'surname' => 'Smith',
            ],
        ], Elastica\Script\Script::LANG_PAINLESS);

        $dbal = new DBAL($typeMock);
        $dbal->updateByQuery($query, $script, 'script');
    }

    public function testAdd(): void
    {
        $responseMock = \Mockery::mock(Elastica\Response::class);
        $responseMock->shouldReceive('isOk')->once()->andReturn(true);

        $indexMock = \Mockery::mock(Elastica\Index::class);
        $indexMock->shouldReceive('refresh')->once();

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('getIndex')->once()->andReturn($indexMock);
        $typeMock->shouldReceive('addDocuments')->once()->withArgs(function (array $documents, array $options) {
            /** @var Elastica\Document $document */
            $this->assertCount(1, $documents);
            $document = $documents[0];
            $this->assertInstanceOf(Elastica\Document::class, $document);
            $this->assertMatchesJsonSnapshot(\json_encode($document->toArray()));
            $this->assertMatchesJsonSnapshot(\json_encode($options));

            return true;
        })->andReturn($responseMock);

        $dbal = new DBAL($typeMock);
        $dbal->add(new Document('new_id', new Map([
            'name' => 'John',
            'surname' => 'Smith',
        ])));
    }

    public function testDeleteByIds(): void
    {
        $indexMock = \Mockery::mock(Elastica\Index::class);
        $indexMock->shouldReceive('refresh')->once();

        $typeMock = \Mockery::mock(Elastica\Type::class);
        $typeMock->shouldReceive('deleteIds')->once()->with(['id1', 'id2', 'id3']);
        $typeMock->shouldReceive('getIndex')->once()->andReturn($indexMock);

        $dbal = new DBAL($typeMock);
        $dbal->deleteByIds('id1', 'id2', 'id3');
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

    public function testGetTypeWhenTypeIsNotSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Elastica\Type is not set');

        $dbal = new class() extends DBAL {
            public function __construct()
            {
                // ooops, parent::__construct is not called
            }

            public function getStuff() {
                $this->search(new Elastica\Query(new Elastica\Query\MatchAll()));
            }
        };

        $dbal->getStuff();
    }
}
