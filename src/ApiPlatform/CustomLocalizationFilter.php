<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
class CustomLocalizationFilter extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];

        if($property === 'localization') {
            $localizationIds = [];
            foreach (explode(',',$value) as $localization) {
                $localizationIds[] = intval($localization);
            }

            $queryBuilder->andWhere(sprintf('%s.localization in (:localization)', $alias));
            $queryBuilder->setParameter('localization', $localizationIds);
        }
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'localization' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => '1,2,3',
                ],
            ],
        ];
    }
}

?>