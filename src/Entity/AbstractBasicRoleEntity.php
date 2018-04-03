<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Security\Core\User\UserInterface;

/** @MappedSuperclass */
abstract class AbstractBasicRoleEntity {
  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="User", cascade={"remove"})
   * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
   */
  private $user;

  /**
   * @ORM\Column(type="string", length=24)
   */
  private $role;

  public function getRights() {
    return $this->role;
  }

  /** @return UserInterface $user */
  public function getUser() {
    return $this->user;
  }

  /** @param UserInterface $user - user */
  public function setUser($user) {
    $this->user = $user;
  }

  /** Converts the text into issue role constant.
   * @param string $roleText - string for the user rights (admin/read/write/anonwrite)
   */
  public function setRole($roleText) {
    switch ($roleText) {
      case 'admin':
      case Board::ROLE_ADMIN:
        $role = Board::ROLE_ADMIN; break;
      case 'anonwrite':
      case Board::ROLE_ANON:
        $role = Board::ROLE_ANON; break;
      case 'write':
      case Board::ROLE_WRITE:
        $role = Board::ROLE_WRITE; break;
      case 'read':
      case Board::ROLE_READ:
      default:
        $role = Board::ROLE_READ; break;
    }
    $this->role = $role;
  }
}