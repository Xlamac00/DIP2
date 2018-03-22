<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class UserRepository extends ServiceEntityRepository  implements UserLoaderInterface {
  private $registry;
  private $manager;
  /** @var  User */
  private $user;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, User::class);
    $this->registry = $registry;
    $this->manager = $registry->getManager();
  }

  /** Loads user with unique user link.
   * @param $userLink - unique 20 characters user link
   *
   * @return User
   * */
  public function loadUserByUsername($userLink) {
    if(!isset($this->user) || $this->user->getUniqueLink() != $userLink)
      $this->user = $this->findOneBy(["link" => $userLink, "googleId" => null]);
    if($this->user instanceof User)
      return $this->user;
    else
      throw new UsernameNotFoundException(
        sprintf('Username "%s" does not exist.', $userLink)
      );
  }

  public function loadUserByGoogleId($googleId) {
    if(!isset($this->user) || $this->user->getGoogleId() != $googleId)
      $this->user = $this->findOneBy(["googleId" => $googleId]);
    if($this->user instanceof User)
      return $this->user;
    else
      throw new UnsupportedUserException(
        sprintf('User with google id "%s" does not exist.', $googleId)
      );
  }

  public function updateUsername($newName) {
    $this->user->setUsername($newName);
    $this->manager->persist($this->user);
    $this->manager->flush();
  }

  public function createNewAnonymousUser($userLink) {
    $user = new User();
    $user->setUsername('Anonymous user');
    $user->setLink($userLink);
    $user->setColor($this->getRandomColor());
    $this->manager->persist($user);
    $this->manager->flush();
    $this->user = $user;
    return $user;
  }

  /** Creates user account from Google Account data.
   * @param $userLink - my own 23 characters long unique id
   * @param $googleId - google id, usually 21 characters
   * @param $name - google full name
   * @param $email - google email
   * @param $image - link to google image
   *
   * @return User
   */
  public function createNewGoogleUser($userLink, $googleId, $name, $email, $image) {
    $user = new User();
    $user->setUsername($name);
    $user->setLink($userLink);
    $user->setGoogleId($googleId);
    $user->setColor($this->getRandomColor());
    $user->setEmail($email);
    $user->setImageLink($image);
    $this->manager->persist($user);
    $this->manager->flush();
    $this->user = $user;
    return $user;
  }

  private function getRandomColor() {
    //    $colors = ['d32f2f', 'c2185b', '7b1fa2', '512da8', '303f9f', '1976d2',
    //      '0097a7',  '00796b', '388e3c', '689f38', 'afb42b', 'fbc02d', '5d4037'];
    $colors = ['ad1457', 'ab47bc', '7e57c2', '5c6bc0', '42a5f5',
      '00bcd4', '4db6ac',  '66bb6a', '9ccc65', 'c0ca33'];
    $i = rand(0, sizeof($colors)-1);
    return '#'.$colors[$i];
  }


}
