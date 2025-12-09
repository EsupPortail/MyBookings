<?php

namespace App\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ServiceProvider implements ProviderInterface
{
    public function __construct(
        private Security $security, 
        private EntityManagerInterface $entityManager, 
        #[Autowire('%platform_mode%')] private string $platformMode, 
        private ProviderInterface $collectionProvider
    ) {}

    /**
     * @inheritDoc
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();
        $type =  $context['filters']['type'] ?? 'Bookings';
        if (!$user) {
            throw new \LogicException('User not found or not authenticated.');
        }
        if ($this->platformMode === 'ressourcerie') {
            return $this->collectionProvider->provide($operation, $uriVariables, $context);
        }

        $acls = array_map(fn($acl) => $acl->getId(), $user->getAcls()->toArray());

        $queryBuilder = $this->buildQueryBuilder($user, $acls, $type);

        return $queryBuilder->getQuery()->getResult();
    }

    private function checkUserAdmin($user): bool
    {
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_ADMIN_RESSOURCERIE', $roles, true)) {
            return true;
        }

        return false;
    }

    private function buildQueryBuilder($user, $acls, $type)
    {
        if ($this->checkUserAdmin($user)) {
            return $this->entityManager->createQueryBuilder()
                ->select('s')
                ->from(Service::class, 's')
                ->where('s.type = :type')
                ->setParameter('type', $type);
        }

        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Service::class, 's')
            ->join('s.acls', 'a')  // Utiliser l'alias de la relation
            ->where('a.id in (:roles)')
            ->andWhere('s.type = :type')
            ->setParameter('roles', $acls)
            ->setParameter('type', $type);
    }
}