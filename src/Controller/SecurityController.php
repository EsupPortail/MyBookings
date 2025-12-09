<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\Mail;
use App\Service\ThemeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class SecurityController extends AbstractController
{
    public function __construct(
        #[Autowire('%login_link_cas_auth_only%')]
        private array $casAuthOnlyDomains,
        private RateLimiterFactoryInterface $loginFormLimiter,
        private readonly ThemeService $themeService,
    )
    {}

    #[Route('/login', name: 'app_login_page', methods: ['GET'])]
    public function login(): Response
    {
        if($this->getUser()) {
            return $this->redirectToRoute('app_ressource');
        } else {
            return $this->render('home/index.html.twig', [
                'theme_css' => $this->themeService->generateCssVariables(),
            ]);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function requestLoginLink(LoginLinkHandlerInterface $loginLinkHandler, UserRepository $userRepository, Request $request,Mail $mailService): Response
    {
        if ($request->isMethod('POST')) {

            $limiter = $this->loginFormLimiter->create($request->getClientIp());
            if (false === $limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }

            $email = $request->getPayload()->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            $notAllowed=false;
            if (is_null($user))
                $notAllowed=true;
            else
            {
                $emailDomain = substr(strrchr($user->getEmail(), "@"), 1);
                foreach ($this->casAuthOnlyDomains as $domainPattern) {
                    if (fnmatch($domainPattern, $emailDomain)) {
                        $notAllowed=true;
                    }
                }
            }

            if ($notAllowed==true)
                return new Response('Not allowed', 403);
            else
            {
                $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
                $loginLink = $loginLinkDetails->getUrl();

                $mailService->sendLoginLink($user, $loginLink);
            }
        }

        return new Response('', 200);

    }

    #[Route('/login_link_check', name: 'login_link_check')]
    public function check(): never
    {
        throw new \LogicException('This code should never be reached');
    }
}