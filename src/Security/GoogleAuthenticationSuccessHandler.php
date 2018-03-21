<?php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\HttpFoundation\Cookie;

class GoogleAuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler {

  public function __construct( HttpUtils $httpUtils, array $options ) {
    parent::__construct( $httpUtils, $options );
  }

  public function onAuthenticationSuccess( Request $request, TokenInterface $token ) {
      $response = parent::onAuthenticationSuccess( $request, $token );
      $user = $token->getUser();
      if(! ($user instanceof User))
        throw new UnsupportedUserException('Google authentication: on authentication success: username not found');
      $google_id = $user->getGoogleId();

      // set cookie with new user id
      $cookie = new Cookie('googleId', $google_id, strtotime('now + 3 months'));
      $response->headers->setCookie($cookie);
      return $response;
  }
}