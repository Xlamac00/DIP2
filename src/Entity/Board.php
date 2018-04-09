<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BoardRepository")
 * @ORM\Table(name="board")
 */
class Board extends AbstractSharableEntity {

  public function __construct() {
    parent::__construct();
    $this->issues = new ArrayCollection();
    $this->favorite = false;
  }

  /**
   * @ORM\OneToMany(targetEntity="Issue", mappedBy="board")
   */
  private $issues;

  /**
   * @return Collection|Issue[]
   */
  public function getIssues() {
    return $this->issues;
  }

  public function removeIssue($issue) {
    $this->issues->removeElement($issue);
  }

  /**
   * @param Issue $issue
   */
  public function setIssue($issue) {
    $this->issues[] = $issue;
  }

  public function getUrl() {
    return 'b/'.parent::getUrl();
  }

  /** Users with rights to see this board */
  private $users;

  public function setUsers($users) {
    $this->users = $users;
  }

  public function getUsers() {
    return $this->users;
  }

  /** Variable with array of users that contributed to this board.
   * Has to be manually set (eg. from controllers) */
  private $activeUsers;


  /**
   * @ORM\Column(type="string", length=7)
   */
  private $color;

  public function setColor($color) {
    if(strlen($color) == 7)
      $this->color = $color;
    elseif(strlen($color) == 6)
      $this->color = '#'.$color;
    else
      $this->color = '#008ba3';
  }

  public function getColor() {
    return $this->color;
  }

  public function getBackground() {
    return 'bg-'.substr($this->color,1);
  }

  /**
   * @param array $usersArray
   */
  public function setActiveUsers($usersArray) {
    $this->activeUsers = $usersArray;
  }

  public function getActiveUsers() {
    return $this->activeUsers;
  }
}
