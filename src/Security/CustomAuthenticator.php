<?php
namespace App\Security;

use App\Repository\BoardRoleRepository;
use App\Repository\IssueRoleRepository;
use App\Repository\UserShareRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CustomAuthenticator extends AbstractGuardAuthenticator {
  private $router;
  private $issueRepository;
  private $boardRepository;
  private $userRepository;

  // Not required by GuardAuthneticator, added because of UrlGenerator
  public function __construct(UrlGeneratorInterface $router, IssueRoleRepository $issueRepository,
                              BoardRoleRepository $boardRoleRepository, UserShareRepository $userShareRepository) {
    $this->router = $router;
    $this->issueRepository = $issueRepository;
    $this->boardRepository = $boardRoleRepository;
    $this->userRepository = $userShareRepository;
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
    $uri = $request->server->get('REQUEST_URI');
    preg_match('/\/[i,b,u,g]\/[0-9A-z]{8}\//', $uri, $matches); // get page id from url
    return array(
      'clientId' => $request->cookies->get('clientId'),
      'googleId' => $request->cookies->get('googleId'),
      'pageId' => sizeof($matches) > 0 ? substr($matches[0], 3, 8) : null,
      'pageType' => sizeof($matches) > 0 ? substr($matches[0], 1, 1) : null,
      'shareLink' => key($request->query->all())
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
    if($credentials['pageType'] === 'i') { // issue
      try { // check database if the user has rights to view this issue
        $role = $this->issueRepository->checkUsersRights($credentials['pageId'], $credentials['clientId'], $credentials['googleId']);
      }
      catch (AuthenticationException $e) { // user has no rights for the issue - check is there is share link
        if(strlen($credentials['shareLink']) == 32) // there is share link set
          $role = $this->issueRepository->checkShareLinkRights($credentials['shareLink'], $credentials['pageId'], $user);
        else
          return false;
      }
      $user->setPagePermission($credentials['pageId'], $role);
      return true;
    }
    else if($credentials['pageType'] === 'b') { // board
      try { // check database if the user has rights to view this board
        $role = $this->boardRepository->checkUsersRights($credentials['pageId'], $credentials['clientId'], $credentials['googleId']);
      }
      catch (AuthenticationException $e) { // user has no rights for the board - check is there is share link
        if(strlen($credentials['shareLink']) == 32) {// there is share link set
          $role = $this->boardRepository->checkShareLinkRights($credentials['shareLink'], $credentials['pageId'], $user);
        }
        else
          return false;
      }
      $user->setPagePermission($credentials['pageId'], $role);
      return true;
    }
    else if($credentials['pageType'] === 'u') { // shared link for specific user
      $role = $this->userRepository->checkShareLinkRights($credentials['shareLink'], $credentials['pageId'], $user);
      $user->setPagePermission($credentials['pageId'], $role);
      return true;
    }
    else if($credentials['pageType'] === 'g') { // shared link for specific user for only one gauge
      $role = $this->userRepository->checkShareLinkRights($credentials['shareLink'], $credentials['pageId'], $user,true);
      $user->setPagePermission($credentials['pageId'], $role);
      return true;
    }
    else
      return true;
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
    $userLink = $token->getUser()->getAnonymousLink();
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
//    die('chyba'.$request->headers->get('referer'));
    new RedirectResponse('/error/404');
  }

  public function start(Request $request, AuthenticationException $authException = null) {
//    die('chyba aut');
    new RedirectResponse('/custom-authenticator-error');
  }

  public function supportsRememberMe() {
    return true;
  }
}