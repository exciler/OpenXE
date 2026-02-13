<?php

namespace OpenXE\Security;

use Doctrine\ORM\EntityManagerInterface;
use OpenXE\Entity\Users\UserOnline;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PreAuthenticatedUserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class LegacyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    private ?UserOnline $userOnline = null;

    public function supports(Request $request): ?bool
    {
        $this->userOnline = $this->entityManager->getRepository(UserOnline::class)->find($request->cookies->get('PHPSESSID'));
        if ($this->userOnline instanceof UserOnline) return null;
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        if (!$this->userOnline instanceof UserOnline || $this->userOnline != $request->cookies->get('PHPSESSID'))
            $id = $this->userOnline->getUser()->getId();

        return new SelfValidatingPassport(new UserBadge($id ?? null));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($request->getBasePath().'/index.php');
    }
}