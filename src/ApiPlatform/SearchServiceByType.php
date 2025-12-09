<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
class SearchServiceByType extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
            if($property === 'type') {
                $queryBuilder
                    ->andWhere(sprintf('%s.type = :type', $alias))
                    ->setParameter('type', $value);
            }
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'type' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => 'service-type',
                ],
            ],
        ];
    }
}

?>