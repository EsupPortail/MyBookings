<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use YRaiso\CasAuthBundle\Security\AuthenticationEntryPoint;

class CustomEntrypoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        #[Autowire('%auth_type%')]
        private readonly string $authType,
        #[Autowire('%cas%')]
        private readonly array $cas
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        if($this->authType === 'CAS') {
            $casEntryPoint = new AuthenticationEntryPoint($this->cas);
            return $casEntryPoint->start($request, $authException);
        }

        return new RedirectResponse(302, $request->getUri());
    }
}