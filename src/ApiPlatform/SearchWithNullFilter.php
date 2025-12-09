<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

class SearchWithNullFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->where(sprintf('%s.Service = :serviceId', $rootAlias));
        $queryBuilder->setParameter('serviceId', $context['filters']['ServiceId']);
        if(isset($context['filters']['ServiceNull']) && $context['filters']['ServiceNull'] === 'true') {
            $queryBuilder->orWhere(sprintf('%s.Service IS NULL', $rootAlias));
        }
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'ServiceId' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => '123',
                ],
            ],
            'ServiceNull' => [
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