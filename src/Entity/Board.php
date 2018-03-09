<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BoardRepository")
 * @ORM\Table(name="board")
 */
class Board
{
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

  private $issues = [];

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return mixed
   */
  public function getIssues() {
    return $this->issues;
  }

  /**
   * @param mixed $issue
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
