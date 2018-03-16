<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BoardRepository")
 * @ORM\Table(name="board")
 */
class Board {
  public function __construct() {
    $this->issues = new ArrayCollection();
  }

  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=100)
   */
  private $name;

  /**
   * @ORM\Column(type="string", length=100)
   */
  private $link;

  /**
   * @ORM\OneToMany(targetEntity="Issue", mappedBy="board")
   */
  private $issues;

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

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

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }
}
