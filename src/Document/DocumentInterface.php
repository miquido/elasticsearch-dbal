<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Document;

use Miquido\DataStructure\HashMap\HashMapInterface;

interface DocumentInterface
{
    public function hasId(): bool;

    /**
     * @return string
     * @throws \LogicException
     */
    public function getId(): string;
    public function getData(): HashMapInterface;
}