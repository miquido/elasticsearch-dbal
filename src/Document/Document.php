<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Document;

use Miquido\DataStructure\HashMap\HashMapInterface;

final class Document implements DocumentInterface
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var HashMapInterface
     */
    private $data;

    public function __construct(?string $id, HashMapInterface $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function getId(): string
    {
        if (!$this->hasId()) {
            throw new \LogicException('Id is not set');
        }

        return $this->id;
    }

    public function hasId(): bool
    {
        return \is_string($this->id);
    }

    public function getData(): HashMapInterface
    {
        return $this->data;
    }
}