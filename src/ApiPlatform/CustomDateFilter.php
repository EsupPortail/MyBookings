<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class CustomDateFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];

        if ($property === 'dateStart') {
            $queryBuilder
                ->andWhere(sprintf('%s.dateStart BETWEEN :dateStart AND %s.dateEnd', $alias, $alias))
                ->orWhere(sprintf(':dateStart BETWEEN %s.dateStart AND %s.dateEnd', $alias, $alias))
                ->setParameter('dateStart', $value);
        } elseif ($property === 'dateEnd') {
            $queryBuilder
                ->andWhere(sprintf('%s.dateEnd BETWEEN %s.dateStart AND :dateEnd', $alias, $alias))
                ->orWhere(sprintf(':dateEnd BETWEEN %s.dateStart AND %s.dateEnd', $alias, $alias))
                ->setParameter('dateEnd', $value);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'dateStart' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'example' => '2025-01-15T00:00:00Z',
                ],
            ],
            'dateEnd' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'date-time',
                    'example' => '2025-01-31T23:59:59Z',
                ],
            ],
        ];
    }
}

?>
