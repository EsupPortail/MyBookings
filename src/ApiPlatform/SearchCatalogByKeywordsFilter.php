<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
class SearchCatalogByKeywordsFilter extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        if($property === 'keywords') {
            $queryBuilder
                ->andWhere(sprintf('%s.title LIKE :keywords OR %s.description LIKE :keywords', $alias, $alias))
                ->setParameter('keywords', '%'.$value.'%');
        }
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'keywords' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => 'mot-clé',
                ],
            ],
        ];
    }
}

?>