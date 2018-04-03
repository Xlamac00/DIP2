<?php
namespace App\Security\User;

use App\Repository\UserRepository;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class OwnAuthUserProvider extends OAuthUserProvider {
  private $repository;

  public function __construct(UserRepository $userRepository) {
    $this->repository = $userRepository;
  }

  /**
   * Loads the user by a given UserResponseInterface object.
   *
   * @param UserResponseInterface $response
   *
   * @return UserInterface
   * @throws AccountExpiredException if the google id cookie is empty
   */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response) {
//      die("ASDSA");
//      die($response);
      try {
        $user = $this->repository->loadUserByGoogleId($response->getUsername());
      }
      catch (UnsupportedUserException $e) { // create new user
        $request = Request::createFromGlobals();
        $clientId = $request->cookies->get('clientId');
        if(strlen($clientId) <= 0) // google id cookie is empty
          throw new AccountExpiredException('Empty clientId cookie');
        $user = $this->repository->createNewGoogleUser($clientId, $response->getUsername(),
          $response->getNickname(), $response->getEmail(), $response->getProfilePicture());
      }
      return $user;
    }
}