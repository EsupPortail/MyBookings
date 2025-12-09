<?php

namespace App\Controller;
use App\Entity\CatalogueResource;
use App\Entity\Service;
use App\Repository\BookingRepository;
use App\Repository\ResourceRepository;
use App\Service\CalendarService;
use App\Service\ThemeService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class HomeController extends AbstractController
{
    public function __construct(
        #[Autowire('%platform_mode%')]
        private readonly string $platFormMode,
        private readonly ThemeService $themeService,
    )
    {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if($this->platFormMode === 'demo') {
            return $this->redirectToRoute('app_login_page');
        } elseif($this->platFormMode === 'ressourcerie'){
            return $this->redirectToRoute('ressoucerie_index');
        } else {
            return $this->redirectToRoute('app_ressource');
        }
    }

    #[Route('/me/', name: 'app_profile')]
    #[Route('/book/', name: 'app_ressource')]
    #[Route('/about', name: 'app_about')]
    #[Route('/changelog', name: 'app_changelog')]
    #[Route('/help', name: 'app_help')]
    public function routes(): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route('/book/resource/{id}', name: 'app_direct_book')]
    #[Route('/book/resource/{id}/now', name: 'app_instant_direct_book')]
    #[Route('/book/resource/{id}/schedule', name: 'app_schedule_direct_book')]
    #[IsGranted('bookResource', subject: 'id',message:"Accès refusé", statusCode: 401)]
    public function secureBookingRoutes($id): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route('/me/{id}', name: 'app_book_view')]
    #[IsGranted('viewBookings', subject: 'id',message:"Accès refusé", statusCode: 401)]
    public function viewBooking($id)
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route('/book/catalog/{id}', name: 'app_catalog_book')]
    #[Route('/book/catalog/{id}/now', name: 'app_catalog_book_now')]
    #[Route('/book/catalog/{id}/schedule', name: 'app_catalog_book_schedule')]
    #[IsGranted('canAllowedUserView', subject: 'id',message:"Accès refusé", statusCode: 401)]
    public function secureCatalogRoutes($id): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }
    #[Route('/administration/site/{idSite}', name: 'app_site_id')]
    #[Route('/administration/site/{idSite}/edit', name: 'app_site_edit')]
    #[Route('/administration/site/{idSite}/booking', name: 'app_site_bookings')]
    #[Route('/administration/site/{idSite}/users', name: 'app_site_users')]
    #[Route('/administration/site/{idSite}/workflows', name: 'app_workflows_list')]
    #[Route('/administration/site/{idSite}/workflows/create', name: 'app_workflows_create')]
    #[Route('/administration/site/{idSite}/workflows/{id}', name: 'app_workflow_id')]
    #[Route('/administration/site/{idSite}/workflows/{id}/edit', name: 'app_workflow_edit')]
    #[Route('/administration/site/{idSite}/catalogue', name: 'app_catalogue_list')]
    #[Route('/administration/site/{idSite}/catalogue/{id}', name: 'app_catalogue_id')]
    #[Route('/administration/site/{idSite}/catalogue/{id}/{action}', name: 'app_catalogue_id_action')]
    #[Route('/administration/site/{idSite}/periods', name: 'app_catalogue_periods')]
    #[Route('/administration/site/{idSite}/periods/{id}', name: 'app_catalogue_periods_id')]
    #[IsGranted('viewService', 'service', 'Accès refusé', 401)]
    public function services(#[MapEntity(mapping: ['idSite' => 'id'])] Service $service): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route('/administration/', name: 'app_admin')]
    #[Route('/administration/site', name: 'app_site_admin')]
    #[IsGranted('moderateService', null, "Accès refusé", 401)]
    public function viewServicePage(): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route('/manage/', name: 'app_manage')]
    #[Route('/manage/category/', name: 'app__manage_category')]
    #[Route('/manage/category/{action}', name: 'app__manage_category_action')]
    #[Route('/manage/localization/', name: 'app__manage_localization')]
    #[Route('/manage/localization/{action}', name: 'app__manage_localization_action')]
    #[Route('/manage/site/', name: 'app__manage_site')]
    #[Route('/manage/site/{action}', name: 'app_site_action')]
    #[Route('/manage/group/', name: 'app__manage_query')]
    #[Route('/manage/rules/', name: 'app__manage_rules')]
    #[Route('/manage/actuators/', name: 'app__manage_actuators')]
    #[IsGranted('ROLE_ADMIN', message:"Accès refusé", statusCode: 401)]
    public function manageApplication(): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route('/planning/catalog/{id}/list', name: 'planning_catalog_list')]
    #[Route('/planning/catalog/{id}/card', name: 'planning_catalog_card')]
    #[IsGranted('canAllowedUserView', subject: 'id',message:"Accès refusé", statusCode: 401)]
    public function getPlanning($id): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }
    #[Route('/planning/catalog/{id}/card/anonymous', name: 'planning_catalog_card_anonymous')]
    #[Route('/planning/catalog/{id}/list/anonymous', name: 'planning_catalog_list_anonymous')]
    public function getPublicPlanning(): Response
    {
        return $this->render('home/index.html.twig', [
            'theme_css' => $this->themeService->generateCssVariables(),
        ]);
    }

    #[Route('/generate/site/{idSite}/catalogue/{catalogue}.ics', name: 'app_catalogue_id_ics')]
    public function generateCatalogueIcs(CatalogueResource $catalogue, BookingRepository $bookingRepository, CalendarService $calendarService) {
        $date = new \DateTime();
        $bookings = $bookingRepository->findBookByCatalog($catalogue, $date->modify('this week monday'));
        // Tri par titre :
        usort($bookings, function ($a, $b) {
            return strcmp($a->getResource()->getValues()[0]->getTitle(), $b->getResource()->getValues()[0]->getTitle());
        });

        $calendarComponent = $calendarService->generateCalendar($bookings);

        // 4. Set HTTP headers
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');

        // 5. Output
        return new Response($calendarComponent, 200);
    }

    #[Route('/generate/site/{idSite}/resource/{id}.ics', name: 'app_ressources_id_ics')]
    public function generateRessourcesIcs($id, ResourceRepository $resourceRepository, CalendarService $calendarService) {
        $resource = $resourceRepository->find($id);
        if($resource) {
            $bookings = $resource->getBookings()->getValues();
            $calendarComponent = $calendarService->generateCalendar($bookings);

            // 4. Set HTTP headers
            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="cal.ics"');

            // 5. Output
            return new Response($calendarComponent, 200);
        }
        return new HttpException('400', 'No Resource found');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}
}
