<?php

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
class CustomRolesFilter extends AbstractFilter
{
    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];

        if($property === 'groups') {
            $GroupIds = [];
            $queryAlias = 'Provisions_a1';
            if (!in_array('Provisions_a1', $queryBuilder->getAllAliases())) {
                $queryBuilder->innerJoin(sprintf('%s.Provisions', $alias), 'p');
                $queryAlias = 'p';
            }
            $queryBuilder->leftJoin($queryAlias.'.groups', 'g');
            foreach (explode(',',$value) as $role) {
                if(strpos($role, 'ROLE_GROUP') !== false) {
                    $id = explode('ROLE_GROUP_', $role);
                    $GroupIds[] = intval($id[1]);
                }
            }
            $queryBuilder->andWhere('g.id in (:groupArray)')
                ->setParameter('groupArray', $GroupIds);
        }
    }
    public function getDescription(string $resourceClass): array
    {
        return [
            'roles' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => 'ROLE_GROUP_1,ROLE_GROUP_2',
                ],
            ],
            'groups' => [
                'property' => null,
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'example' => 'ROLE_GROUP_1,ROLE_GROUP_2',
                ],
            ],
        ];
    }
}

?>