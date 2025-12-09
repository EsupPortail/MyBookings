<?php
namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class TargetIdFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ($property !== 'targetId') {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // ici tu dois adapter à ce que signifie targetId dans ton contexte
        // par exemple si c’est un match avec un champ réel
        $queryBuilder
            ->andWhere("$alias.title = :targetId")
            ->setParameter('targetId', $value);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'targetId' => [
                'property' => 'targetId',
                'required' => false,
                'schema' => [
                    'type' => 'integer',
                    'example' => 123,
                ],
            ],
        ];
    }
}