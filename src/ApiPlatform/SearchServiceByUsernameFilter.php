<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
class SearchServiceByUsernameFilter extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        // Ne traite que les propriétés autorisées (si properties est défini dans l'attribut ApiFilter)
        if (!$this->isPropertyEnabled($property, $resourceClass)) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // Join une seule fois si l'alias 'p' n'existe pas encore
        if (!in_array('p', $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin(sprintf('%s.acls', $alias), 'p');
        }

        if($property === 'username') {
            $queryBuilder
                ->leftJoin('p.user', 'u')
                ->andWhere('u.username LIKE :username')
                ->setParameter('username', '%'.$value.'%');
        } elseif($property === 'type') {
            $queryBuilder->leftJoin('p.service', 's');
            $queryBuilder->andWhere('s.type = :type')
                ->setParameter('type', $value);
        }
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'username' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => 'jedupont',
                ],
            ],
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