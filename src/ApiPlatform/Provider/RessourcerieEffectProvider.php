<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RessourcerieEffectProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire('@api_platform.doctrine.orm.state.collection_provider')] private ProviderInterface $collectionProvider,
        private Security $security,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        $context['filters']['status'] = $this->checkUserAdmin($user, $context['filters']['service.id'] ?? null) ? ($context['filters']['status'] ?? null) : 'rc_published';
        $context['filters']['service.type'] = 'ressourcerie';
        return $this->collectionProvider->provide($operation, $uriVariables, $context);
        /*if (isset($context['filters']['status']) && $context['filters']['service.id']) {

        } elseif(isset($context['filters']['status'])) {
            if(in_array("ROLE_ADMIN_RESSOURCERIE", $user->getRoles())) {
                return $this->collectionProvider->provide($operation, $uriVariables, $context);
            }
        } else {
            $context['filters']['status'] = 'rc_published';
            return $this->collectionProvider->provide($operation, $uriVariables, $context);
        }*/
    }

    private function checkUserAdmin($user, $serviceId = null): bool
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_ADMIN_RESSOURCERIE', $roles, true)) {
            return true;
        }

        if($serviceId && in_array('ROLE_ADMINSITE_'.$serviceId, $roles, true)) {
            return true;
        }

        return false;
    }
}