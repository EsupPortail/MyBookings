<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Security\CatalogVoter;
use Symfony\Bundle\SecurityBundle\Security;

class CatalogCollectionDataProvider implements ProviderInterface
{
    public function __construct(private ProviderInterface $itemProvider, private ProviderInterface $collectionProvider, private Security $security, private CatalogVoter $voter)
    {
    }

    /**
     * @throws \Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): null|array|object
    {
        if ($operation instanceof CollectionOperationInterface) {

            if (!isset($context['filters']) || !$this->hasValidFilters($context)) {
                return [];
            }

            return $this->collectionProvider->provide($operation, $uriVariables, $context);

        }

        if(!$this->security->isGranted('canAllowedUserView', $uriVariables['id'])) {
            throw new \Exception('Not found', 404);
        }

        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }

    private function hasValidFilters(array $context): bool
    {
        $isValid = true;
        foreach ($context['filters'] as $key=> $filter) {
            if(!in_array($key, ['Provisions.dateStart', 'Provisions.dateEnd', 'groups', 'localization', 'subType', 'service.id', 'resource.id'])) {
                $isValid = false;
            }
        }
        return $isValid;
    }
}