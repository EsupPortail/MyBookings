<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\AccessMapInterface;

class ShibAuthenticator extends AbstractAuthenticator
{
    public function __construct(#[Autowire('%auth_type%')] private string $authType, #[Autowire(service: 'security.access_map')] private AccessMapInterface $accessMap)
    {
    }

    public function supports(Request $request): ?bool
    {
        if(strtoupper($this->authType) !== 'SHIBBOLETH') {
            return false;
        }

        // Désactiver l'authentification pour les routes PUBLIC_ACCESS
        [$attributes] = $this->accessMap->getPatterns($request);
        if ($attributes && \in_array('PUBLIC_ACCESS', $attributes, true)) {
            return false;
        }
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $entity = '';
        $username = explode( '@', $_SERVER['HTTP_EPPN'])[0];
        if (preg_match('/@([a-zA-Z0-9_-]+)\./', $_SERVER['HTTP_EPPN'], $matches)) {
            $entity = $matches[1];
        }
        $passport = new SelfValidatingPassport(new UserBadge($username, null, [
            'entity' => $entity,
            'displayName' => $_SERVER['HTTP_DISPLAYNAME'],
            'email' => $_SERVER['HTTP_MAIL'],
        ]));
        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}
