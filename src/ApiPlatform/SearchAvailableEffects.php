<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class SearchAvailableEffects extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        if ($property === 'available') {
            $queryBuilder
                ->leftJoin(sprintf('%s.resource', $alias), 'r')
                ->leftJoin('r.Bookings', 'b')
                ->groupBy('r.id')
                ->having('COUNT(r.id) > COUNT(b.id)')
;
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'available' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'boolean',
                    'example' => true,
                ],
            ],
        ];
    }
}

?>