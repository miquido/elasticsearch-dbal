[![Build](https://travis-ci.org/miquido/elasticsearch-dbal.svg?branch=master)](https://travis-ci.org/miquido/elasticsearch-dbal)
[![Maintainability](https://api.codeclimate.com/v1/badges/608064935172a46d839a/maintainability)](https://codeclimate.com/github/miquido/elasticsearch-dbal/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/608064935172a46d839a/test_coverage)](https://codeclimate.com/github/miquido/elasticsearch-dbal/test_coverage)
[![MIT Licence](https://badges.frapsoft.com/os/mit/mit.svg?v=103)](https://opensource.org/licenses/mit-license.php)

# Elasticsearch DBAL

Wrapper for [https://github.com/ruflin/Elastica](https://github.com/ruflin/Elastica)

- [Installation guide](#installation)
- [Code Samples](#code-samples)
- [Contributing](#contributing)

## Installation 
Use [Composer](https://getcomposer.org) to install the package:

```shell
composer require miquido/elasticsearch-dbal
```

## Code Samples
- [Initialize DBAL object](#initialize-dbal-object)
- [Count documents](#count-documents)
- [Search documents](#search-documents)
- [SearchResult and Document objects](#searchresult-and-document-objects)
- [Create new documents](#create-new-documents)
- [Update documents](#update-documents)
- [Delete documents](#delete-documents)

### Initialize *DBAL* object
*Miquido\Elasticsearch\DBAL* requires *Elastica\Type* object:
```php
<?php

use Miquido\Elasticsearch\DBAL;

$client = new \Elastica\Client();
$type = $client->getIndex('index_name')->getType('type_name');

$dbal = new DBAL($type);
```

### Count documents

```php
<?php

$dbal->countAll(); // count all documents in the type
$dbal->count(new \Elastica\Query(new \Elastica\Query\Terms('field_name', [1, 2, 3]))); // count documents matching query

```

### Search documents
```php
<?php

use Miquido\Elasticsearch\DBAL;

$dbal = new DBAL($type);

$query = new \Elastica\Query(new \Elastica\Query\Terms('field_name', [1, 2, 3]));

$dbal->search($query); // returns 10 documents (default ElasticSearch setting)
$dbal->searchAll($query); // returns all documents (uses scroll api)
$dbal->findOne($query);
$dbal->findByIds('id1', 'id2', 'id2');

```

### SearchResult and Document objects
*search()*, *searchAll()* and *findByIds()* methods return instance of [Miquido\Elasticsearch\Result\SearchResultInterface](src/Result/SearchResultInterface.php)

*findOne()* method returns instance of [Miquido\Elasticsearch\Document\DocumentInterface](src/Document/DocumentInterface.php)

Please also check [miquido/data-structure](https://github.com/miquido/data-structure) library for more details about classes used inside Documents objects.
```php
<?php

use Miquido\Elasticsearch\DBAL;

$dbal = new DBAL($type);

$result = $dbal->search(new \Elastica\Query(new \Elastica\Query\MatchAll()));
$result->count(); // returns number of documents in result
$result->getTotalHits(); // returns number of documents matching the query
$result->getTime(); // returns time of the query
$result->getDocuments()->getAll(); // returns instances of Documents 
$result->getDocuments()->getData(); // returns instance of Miquido\DataStructure\Map\MapCollectionInterface 

$document = $dbal->findOne(new \Elastica\Query());
$document->getId(); // string
$document->getData(); // returns instance of Miquido\DataStructure\Map\MapInterface  
```

### Create new documents
```php
<?php

use Miquido\Elasticsearch\DBAL;
use Miquido\Elasticsearch\Document\Document;
use Miquido\DataStructure\Map\Map;

$dbal = new DBAL($type);
$dbal->add(new Document(
    null /* or string if you want to choose your own ID */, 
    new Map([
        'name' => 'John',
        'surname' => 'Smith',
        'age' => 40,
    ]))
);

// you can also add many documents at once
$dbal->bulkAdd($document1, $document2, ...);

```
### Update documents
```php
<?php

use Miquido\Elasticsearch\DBAL;
use Miquido\Elasticsearch\Document\Document;
use Miquido\DataStructure\Map\Map;

$dbal = new DBAL($type);

// this method will only update 'age' field in document with ID 'documentId'
$dbal->updatePatch(new Document('documentId', new Map([
    'age' => 41,
])));

// you can also add many documents at once
$dbal->bulkUpdatePatch($document1, $document2, ...);
```

### Delete documents
```php
<?php

use Miquido\Elasticsearch\DBAL;

$dbal = new DBAL($type);
$dbal->deleteByIds('id1', 'id2', 'id3');
```

## Contributing

Pull requests, bug fixes and issue reports are welcome.
Before proposing a change, please discuss your change by raising an issue.

