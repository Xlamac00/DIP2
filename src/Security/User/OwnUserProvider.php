<?php
namespace App\Security\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class OwnUserProvider implements UserProviderInterface {
  private $repository;

  public function __construct(UserRepository $userRepository) {
    $this->repository = $userRepository;
  }

  public function loadUserByUsername($userLink) {
    try { // try to use userLink as google id and find user
      $user = $this->repository->loadUserByGoogleId($userLink);
    }
    catch (UnsupportedUserException $e) {
      try { // try to find user by my own user id
        $user = $this->repository->loadUserByUsername($userLink);
      }
      catch (UsernameNotFoundException $e) { // nothing found, create new user
        $user = $this->repository->createNewAnonymousUser($userLink);
      }
    }
    return $user;
  }

  public function refreshUser(UserInterface $user) {
    if (!$user instanceof User) {
      throw new UnsupportedUserException(
        sprintf('Instances of "%s" are not supported.', get_class($user))
      );
    }
    return $user;
  }

  public function supportsClass($class) {
    return User::class === $class;
  }
}