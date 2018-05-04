<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @MappedSuperclass */
abstract class AbstractSharableEntity extends AbstractLinkableEntity {
  const ROLE_WRITE = 'ROLE_ISSUE_WRITE';
  const ROLE_READ = 'ROLE_ISSUE_READ';
  const ROLE_ANON = 'ROLE_ISSUE_ANONWRITE';
  const ROLE_ADMIN = 'ROLE_ISSUE_ADMIN';
  const ROLE_VOID = 'ROLE_ISSUE_NOTHING';
  const ROLE_GAUGE = 'ROLE_ISSUE_GAUGE';

  public function __construct() {
    $this->shareEnabled = true;
  }

  /**
   * @ORM\Column(type="string", length=64)
   */
  private $shareLink;

  /**
   * @ORM\Column(type="boolean")
   */
  private  $shareEnabled;

  /**
   * @ORM\Column(type="string", length=32)
   */
  private  $shareRights;

  /** Variable with array of all users that have rights to see/edit this issue.
   * Deleted users should not be included.
   * Has to be manually set (eg. from boardrepository) */
  private $allUsers;

  /** Variable calculated for current user and his rights for this issue.
   * Has to be manually set (eg. from boardrepository)
   * @var AbstractRoleEntity
   */
  private $userRights;

  /** Generates unique strings for the entity.
   * Creates sharing link and short 8 characters unique id for linkable entity.
   * */
  public function generateLinks() {
    parent::generateLinks();
    $this->shareLink = md5($this->linkId.$this->linkName);
  }

  public function getShareLink() {
    return $this->shareLink;
  }

  /** @return boolean */
  public function isShareEnabled() {
    return $this->shareEnabled;
  }

  /** @param boolean $enabled */
  public function setShareEnabled($enabled) {
    $this->shareEnabled = $enabled;
  }

  public function getShareRights() {
    return $this->shareRights;
  }

  public function setShareRights($right) {
    $this->shareRights = $right;
  }

  /** Rights for the currently logged user to see this issue
   * @param AbstractRoleEntity $rights
   */
  public function setThisUserRights($rights) {
    $this->userRights = $rights;
  }

  public function getThisUserRights() {
    return $this->userRights;
  }

  /** Returns boolean if user can read values from this entity.
   * @return boolean */
  public function canUserRead() {
    if($this->canUserManage() === true) return true; // if he can manage, he can read as well
    return
      $this->userRights->isActive() &&
      $this->userRights->isShareEnabled() &&
      !$this->userRights->isDeleted() &&
      in_array($this->userRights->getRights(),
         [Board::ROLE_ADMIN, Board::ROLE_WRITE, Board::ROLE_ANON, Board::ROLE_READ, Board::ROLE_GAUGE]);
  }

  public function canUserEditGauge() {
    if($this->canUserManage() === true) return true; // if he can manage, he can read as well
    return
      $this->userRights->isActive() &&
      $this->userRights->isShareEnabled() &&
      !$this->userRights->isDeleted() &&
      in_array($this->userRights->getRights(),
        [Board::ROLE_ADMIN, Board::ROLE_WRITE, Board::ROLE_ANON, Board::ROLE_GAUGE]);
  }

  public function canUserWrite() {
    if($this->canUserManage() === true) return true; // if he can manage, he can read as well
    if($this->userRights->isActive() &&
      $this->userRights->isShareEnabled() &&
      !$this->userRights->isDeleted()) {
      if($this->userRights->getUser()->isAnonymous()) // user is anonymous, is it allowed them to write?
        return $this->userRights->getRights() === Board::ROLE_ANON;
      else
        return in_array($this->userRights->getRights(),[Board::ROLE_WRITE, Board::ROLE_ANON]);
    }
    return false;
  }

  public function canUserManage() {
    return
      $this->userRights->isActive() &&
      !$this->userRights->isDeleted() &&
      $this->userRights->getRights() === Board::ROLE_ADMIN;
  }

  public function setAllUsers($usersArray) {
    $this->allUsers = $usersArray;
  }

  public function getAllUsers() {
    return $this->allUsers;
  }
}