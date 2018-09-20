<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch;

use Elastica;
use Miquido\DataStructure\Map\Map;
use Miquido\Elasticsearch\Document\Collection\DocumentCollection;
use Miquido\Elasticsearch\Document\Collection\DocumentCollectionInterface;
use Miquido\Elasticsearch\Document\Document;
use Miquido\Elasticsearch\Document\DocumentInterface;
use Miquido\Elasticsearch\Exception\DocumentNotFoundException;
use Miquido\Elasticsearch\Exception\ElasticsearchQueryException;
use Miquido\Elasticsearch\Result\SearchResult;
use Miquido\Elasticsearch\Result\SearchResultInterface;
use Webmozart\Assert\Assert;

class DBAL implements DBALInterface
{
    /**
     * @var Elastica\Type
     */
    private $type;

    public function __construct(Elastica\Type $type)
    {
        $this->type = $type;
    }

    /**
     * @param Elastica\Query $query
     *
     * @throws ElasticsearchQueryException
     *
     * @return int
     */
    public function count(Elastica\Query $query): int
    {
        return $this->search(Elastica\Query::create($query->toArray())->setSize(0))->getTotalHits();
    }

    /**
     * @throws ElasticsearchQueryException
     *
     * @return int
     */
    public function countAll(): int
    {
        $query = new Elastica\Query(new Elastica\Query\MatchAll());
        $query->setSize(0);

        return $this->search($query)->getTotalHits();
    }

    /**
     * @param Elastica\Query $query
     *
     * @throws ElasticsearchQueryException
     *
     * @return SearchResultInterface
     */
    public function search(Elastica\Query $query): SearchResultInterface
    {
        return $this->createSearchResultFromResultSet($this->executeQuery($query));
    }

    public function searchAll(Elastica\Query $query, $scrollExpiryTime = '1m'): SearchResultInterface
    {
        if ($query->hasParam('size') || $query->hasParam('from')) {
            throw new \InvalidArgumentException('Please do not use from/size with searchAll');
        }

        $documents = [];
        $batch = 10000;
        $totalTime = 0;

        $query = Elastica\Query::create($query->toArray())->setSize($batch);

        $scroll = $this->getType()->createSearch($query)->scroll($scrollExpiryTime);
        foreach ($scroll as $scrollId => $resultSet) {
            $totalTime += $resultSet->getTotalTime();
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $documents = \array_merge(
                $documents,
                $this->mapResultSetToCollection($resultSet)->getAll()
            );
        }

        return new SearchResult(
            new DocumentCollection(...$documents),
            $totalTime,
            \count($documents)
        );
    }

    /**
     * @param Elastica\Query $query
     *
     * @throws DocumentNotFoundException
     * @throws ElasticsearchQueryException
     *
     * @return DocumentInterface
     */
    public function findOne(Elastica\Query $query): DocumentInterface
    {
        $documents = $this->search(Elastica\Query::create($query->toArray())->setSize(1))->getDocuments();
        if (0 === $documents->count()) {
            throw new DocumentNotFoundException('Document not found');
        }

        return $documents->getAll()[0];
    }

    /**
     * @param string ...$ids
     *
     * @return SearchResultInterface
     */
    public function findByIds(string ...$ids): SearchResultInterface
    {
        Assert::minCount($ids, 1, 'Please provide at least one id');

        return $this->searchAll(new Elastica\Query(new Elastica\Query\Ids($ids)));
    }

    /**
     * @param DocumentInterface ...$documents
     *
     * @throws ElasticsearchQueryException
     */
    public function bulkUpdatePatch(DocumentInterface ...$documents): void
    {
        $bulk = new Elastica\Bulk($this->getIndex()->getClient());
        $bulk->setRequestParam('refresh', 'wait_for');

        foreach ($documents as $document) {
            $updateDocument = new Elastica\Bulk\Action\UpdateDocument(new Elastica\Document($document->getId()));
            $updateDocument->setDocument(new Elastica\Document(
                $document->getId(),
                $document->getData()->toArray()
            ));
            $updateDocument->setType($this->getType());
            $bulk->addAction($updateDocument);
        }

        $this->checkResponse($bulk->send());
        $this->getIndex()->refresh();
    }

    /**
     * @param DocumentInterface $document
     *
     * @throws ElasticsearchQueryException
     */
    public function updatePatch(DocumentInterface $document): void
    {
        $this->bulkUpdatePatch($document);
    }

    /**
     * @param Elastica\Query         $query
     * @param Elastica\Script\Script $script
     * @param string                 $scriptKey
     *
     * @throws ElasticsearchQueryException
     */
    public function updateByQuery(Elastica\Query $query, Elastica\Script\Script $script, string $scriptKey = 'source'): void
    {
        $scriptData = new Map($script->toArray()['script']);

        if ('source' !== $scriptKey) {
            $scriptData = $scriptData->rename('source', $scriptKey);
        }

        $response = $this->getType()->request('_update_by_query?refresh=wait_for', Elastica\Request::POST, [
            'query' => $query->getQuery()->toArray(),
            'script' => $scriptData->toArray(),
        ]);

        $this->checkResponse($response);
        $this->getIndex()->refresh();
    }

    /**
     * @param DocumentInterface $document
     *
     * @throws ElasticsearchQueryException
     */
    public function add(DocumentInterface $document): void
    {
        $this->bulkAdd($document);
    }

    /**
     * @param DocumentInterface ...$documents
     *
     * @throws ElasticsearchQueryException
     */
    public function bulkAdd(DocumentInterface ...$documents): void
    {
        $response = $this->getType()->addDocuments(\array_map(
            function (DocumentInterface $document): Elastica\Document {
                return new Elastica\Document(
                    $document->hasId() ? $document->getId() : '',
                    $document->getData()->toArray()
                );
            }, $documents
        ), ['refresh' => 'wait_for']);

        $this->checkResponse($response);
        $this->getIndex()->refresh();
    }

    public function deleteByIds(string ...$ids): void
    {
        if (\count($ids)) {
            $this->getType()->deleteIds($ids);
            $this->getIndex()->refresh();
        }
    }

    /**
     * @param Elastica\Query $query
     *
     * @throws \RuntimeException
     * @throws ElasticsearchQueryException
     *
     * @return Elastica\ResultSet
     */
    public function executeQuery(Elastica\Query $query): Elastica\ResultSet
    {
        $resultSet = $this->getType()->search($query);
        $this->checkResponse($resultSet->getResponse());

        return $resultSet;
    }

    protected function createSearchResultFromResultSet(Elastica\ResultSet $resultSet): SearchResultInterface
    {
        return new SearchResult(
            $this->mapResultSetToCollection($resultSet),
            $resultSet->getTotalTime(),
            $resultSet->getTotalHits()
        );
    }

    protected function mapResultSetToCollection(Elastica\ResultSet $resultSet): DocumentCollectionInterface
    {
        return new DocumentCollection(...\array_map(
            function (Elastica\Result $result): DocumentInterface {
                return new Document(
                    $result->getId(),
                    new Map($result->getData())
                );
            },
            $resultSet->getResults()
        ));
    }

    /**
     * @throws \RuntimeException
     *
     * @return Elastica\Type
     */
    protected function getType(): Elastica\Type
    {
        if (!$this->type instanceof Elastica\Type) {
            throw new \RuntimeException('Type is not set');
        }

        return $this->type;
    }

    /**
     * @throws \RuntimeException
     *
     * @return Elastica\Index
     */
    protected function getIndex(): Elastica\Index
    {
        return $this->getType()->getIndex();
    }

    /**
     * @param Elastica\Response $response
     *
     * @throws ElasticsearchQueryException
     */
    private function checkResponse(Elastica\Response $response): void
    {
        if (!$response->isOk()) {
            throw new ElasticsearchQueryException(\sprintf(
                'Elastic query failed (status: %s)',
                $response->getStatus()
            ));
        }
    }
}
