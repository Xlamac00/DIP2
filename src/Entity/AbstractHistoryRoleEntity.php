<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Security\Core\User\UserInterface;

/** @MappedSuperclass */
abstract class AbstractHistoryRoleEntity extends  AbstractBasicRoleEntity {

  public function __construct() {
    $this->time = new \DateTime("now");
    $this->oldRole = null;
  }

  /**
   * @ORM\Column(type="string", length=24, nullable=true)
   */
  private $oldRole;

  public function setOldRole($role) {
    $this->oldRole = $role;
  }

  public function getOldRole() {
    return $this->oldRole;
  }

  public function isOldRole() {
    return $this->oldRole !== null;
  }

  /**
   * @ORM\Column(type="datetime")
   */
  private $time;
}