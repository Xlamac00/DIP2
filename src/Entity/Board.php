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
}
