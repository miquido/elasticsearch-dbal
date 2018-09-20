<?php

declare(strict_types=1);

namespace Miquido\Elasticsearch\Document;

use Miquido\DataStructure\Map\MapInterface;

interface DocumentInterface
{
    public function hasId(): bool;

    /**
     * @throws \LogicException
     *
     * @return string
     */
    public function getId(): string;

    public function getData(): MapInterface;
}
