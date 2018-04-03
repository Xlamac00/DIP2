<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Security\Core\User\UserInterface;

/** @MappedSuperclass */
abstract class AbstractRoleEntity extends AbstractBasicRoleEntity {

  public function __construct() {
    $this->shareEnabled = true;
    $this->isActive = true;
    $this->isDeleted = false;
  }

  /**
   * @ORM\Column(name="share_enabled", type="boolean")
   */
  private $shareEnabled;

  /**
   * @ORM\Column(name="is_active", type="boolean")
   */
  private $isActive;

  /**
   * @ORM\Column(name="is_deleted", type="boolean")
   */
  private $isDeleted;

  /**
   * @ORM\ManyToOne(targetEntity="BoardShareHistory")
   * @ORM\JoinColumn(name="id_board_history", referencedColumnName="id", nullable=true)
   */
  private $boardHistory;

  /**
   * @param BoardShareHistory $history
   */
  public function setBoardHistory($history) {
    $this->boardHistory = $history;
  }

  /** If the Role was gained by board link
   * @return boolean
   */
  public function isBoardHistory() {
    return $this->boardHistory !== null;
  }

  /** @return BoardShareHistory */
  public function getBoardHistory() {
    return $this->boardHistory;
  }

  public function setActive($bool) {
    $this->isActive = $bool;
  }

  public function isActive() {
    return $this->isActive;
  }

  /**
   * @param boolean $bool
   */
  public function setShareEnabled($bool) {
    $this->shareEnabled = $bool;
  }

  public function isShareEnabled() {
    return $this->shareEnabled;
  }

  public function delete() {
    $this->isDeleted = true;
  }

  public function isDeleted() {
    return $this->isDeleted;
  }
}