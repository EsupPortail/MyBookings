<?php

namespace App\Controller;

use App\Service\ThemeService;
use App\Service\RessourcerieService;
use App\Repository\BookingRepository;
use Symfony\Component\Workflow\Registry;
use App\Repository\WorkflowLogRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CatalogueResourceRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RessourcerieController extends AbstractController
{
    public function __construct(
        private RessourcerieService $ressourcerieService,
        private BookingRepository $bookingRepository,
        private Registry $workflows,
        private WorkflowLogRepository $workflowLogRepository,
        private CatalogueResourceRepository $catalogResourceRepository,
        private SerializerInterface $serializer,
        private readonly ThemeService $themeService,

    ) {}

    #[Route('/ressourcerie', name: 'ressoucerie_index')]
    #[Route('/ressourcerie/me', name: 'ressoucerie_me')]
    #[Route('/ressourcerie/submission', name: 'ressoucerie_submission')]
    #[Route('/ressourcerie/administration/site', name: 'ressoucerie_administration_site')]
    #[Route('/ressourcerie/administration/site/{id}', name: 'ressoucerie_administration_site_id')]
    #[Route('/ressourcerie/administration/site/{id}/users', name: 'ressoucerie_administration_site_id_users')]
    #[Route('/ressourcerie/administration/site/{id}/catalogue', name: 'ressoucerie_administration_site_catalogue')]
    #[Route('/ressourcerie/administration/site/{id}/catalogue/{idCatalogue}', name: 'ressoucerie_administration_site_catalogue_id')]
    #[Route('/ressourcerie/administration/site/{id}/catalogue/{idCatalogue}/edit', name: 'ressoucerie_administration_site_catalogue_id_edit')]
    #[Route('/ressourcerie/administration/site/{id}/bookings', name: 'ressoucerie_administration_site_bookings')]
    #[Route('/ressourcerie/catalog/{id}', name: 'ressoucerie_catalog_id')]
    public function ressourcerie(): Response
    {
        return $this->render('ressourcerie/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route(path: '/ressourcerie/sit', name: 'ressourcerie_sit_moderation')]
    #[Route(path: '/ressourcerie/sit/{action}', name: 'ressourcerie_sit_moderation_action')]
    #[IsGranted('ROLE_ADMIN_RESSOURCERIE')]
    public function sitModeration(): Response
    {
        return $this->render('ressourcerie/index.html.twig');
    }

    #region API

    /**
     * Save effect books for Ressourcerie
     */
    #[Route(path: '/api/effects', name: 'post_effects_book', methods: ['POST'])]
    public function bookEffects(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $this->ressourcerieService->bookEffects($request->get('resourceIds'), $request->get('targetId'), $user->getUsername());
        return new JsonResponse('Effect(s) booked', 200);
    }

    #[Route(path: '/api/effects/{id}/confirm', name: 'confirm_effects', methods: ['POST'])]
    public function confirmEffects($id, Request $request): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $confirm = json_decode($request->get('confirm'));
            $comment = $request->get('comment');
            if($confirm === true) {
                $booking->setConfirmComment($comment);
                $workflow = $this->workflows->get($booking, 'ressourcerie_effect');
                $workflow->apply($booking, 'accept_request');
                try {
                    $this->bookingRepository->add($booking, true);
                } catch (\Exception $e) {
                    return new Response($e->getMessage(), 400);
                }
                return new Response('Demande confirmée', 200);
            }

        }
        return new Response("La demande n'existe pas", 400);
    }

    #[Route(path: '/api/effects/{id}/close', name: 'close_effects', methods: ['POST'])]
    public function closeEffects($id, Request $request): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $workflow = $this->workflows->get($booking, 'ressourcerie_effect');
            $workflow->apply($booking, 'end_request');
            try {
                $this->bookingRepository->add($booking, true);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 400);
            }
            return new Response('Demande clôturée', 200);
        
        }
        return new Response("La demande n'existe pas", 400);
    }

    #[Route(path: '/api/effects/{id}/refuse', name: 'refuse_effects', methods: ['POST'])]
    public function refuseEffects($id): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $workflow = $this->workflows->get($booking, 'ressourcerie_effect');
            $workflow->apply($booking, 'remove_request', ['user' => $this->getUser()->getUserIdentifier()]);
            try {
                $this->bookingRepository->remove($booking, true);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 400);
            }
            return new Response('Demande refusée', 200);
        
        }
        return new Response("La demande n'existe pas", 400);
    }

    #[Route(path: '/api/effects/{id}/cancel', name: 'cancel_effects', methods: ['POST'])]
    public function cancelEffects($id): Response
    {
        $booking = $this->bookingRepository->find($id);
        if($booking) {
            $workflow = $this->workflows->get($booking, 'ressourcerie_effect');
            $workflow->apply($booking, 'remove_request', [
                'user' => $this->getUser()->getUserIdentifier(),
                'action' => 'annulée'
            ]);
            try {
                $this->bookingRepository->remove($booking, true);
            } catch (\Exception $e) {
                return new Response($e->getMessage(), 400);
            }
            return new Response('Demande annulée', 200);
        
        }
        return new Response("La demande n'existe pas", 400);
    }

    #[Route(path: '/api/deposits', name: 'get_deposits', methods: ['GET'])]
    public function getDeposits(): JsonResponse
    {
        $username = $this->getUser()->getUserIdentifier();
        // Get workflow logs by comment containing "username":"$username"
        $workflowLogs = $this->workflowLogRepository->findByTransitionAndUsername('new_submission', $username);

        // For each workflow log, get the associated catalog
        // The catalog ID is in the comment as "catalogId":<id>
        $catalogResources = array_map(function($log) {
            $comment = $log->getComment();
            preg_match('/"catalogId":(\d+)/', $comment, $matches);
            if (isset($matches[1])) {
                $catalogId = $matches[1];
                return $this->catalogResourceRepository->find($catalogId);
            }
            return null;
        }, $workflowLogs);
        $catalogResources = array_filter($catalogResources); // Remove nulls

        //sort by id desc
        usort($catalogResources, function($a, $b) {
            return $b->getId() <=> $a->getId();
        });
        
        // Serialize and return the catalogs
        $serialized = $this->serializer->serialize($catalogResources, 'json', ['groups' => 'ressourcerie::read']);
        return new JsonResponse(json_decode($serialized), 200);
    }

    #endregion

    
    
}