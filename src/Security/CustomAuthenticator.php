<?php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;

class CustomAuthenticator extends AbstractGuardAuthenticator {
  private $router;

  // Not required by GuardAuthneticator, added because of UrlGenerator
  public function __construct(UrlGeneratorInterface $router) {
    $this->router = $router;
  }

  /**
   * Called on every request to decide if this authenticator should be
   * used for the request. Returning false will cause this authenticator
   * to be skipped.
   */
  public function supports(Request $request) {
    if($request->get('service') === 'google') return false;
    return true;
  }

  /**
   * Called on every request. Return whatever credentials you want to
   * be passed to getUser() as $credentials.
   */
  public function getCredentials(Request $request) {
    return array(
      'clientId' => $request->cookies->get('clientId'),
      'googleId' => $request->cookies->get('googleId'),
    );
  }

  public function getUser($credentials, UserProviderInterface $userProvider) {
    $userId = $credentials['clientId'];
    $googleId = $credentials['googleId'];

    if($userId === null) {
      $userId = uniqid('', true); // random user id
    }
    return $userProvider->loadUserByUsername(isset($googleId) ? $googleId : $userId);
  }

  public function checkCredentials($credentials, UserInterface $user) {
//    if($user instanceof User && $user->isAnonymous())
      return true;
//    return false;
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
    $userLink = $token->getUser()->getUniqueLink();
    $userCookie = $request->cookies->get('clientId');
    if($userLink === $userCookie) {
      return null;
    }
    else { // save new user link to cookie
      $cookie = new Cookie('clientId', $userLink, strtotime('now + 3 months'));
      $route = $request->get('_route');
      $params = $request->get('_route_params');

      $url = $this->router->generate($route, $params);
      $response =  new RedirectResponse($url);
      $response->headers->setCookie($cookie);
      return $response;
    }
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
    new RedirectResponse('/custom-authenticator-fail');
  }

  public function start(Request $request, AuthenticationException $authException = null) {
    new RedirectResponse('/custom-authenticator-error');
  }

  public function supportsRememberMe() {
    return true;
  }
}