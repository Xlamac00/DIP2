<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TipsRepository")
 * @ORM\Table(name="tip")
 */
class Tips {
  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @var string $name
   * @ORM\Column(type="string", length=16)
   */
  private $name;

  /** Screen where the should be displayed (eg. dashboard, issue, ...)
   * @var string $screen
   * @ORM\Column(type="string", length=16)
   */
  private $screen;

  /**
   * @var DateTime $shown
   * @ORM\Column(type="date", nullable=true)
   */
  private $shown;

  /**
   * @var User $user
   * @ORM\Column(type="string", length=24)
   */
  private $user_link;

  public function setName($name) {
    $this->name = $name;
  }

  public function setScreen($screen) {
    $this->screen = $screen;
  }

  public function setUser($user) {
    $this->user_link = $user;
  }

  public function setShown() {
    $this->shown = new DateTime("now");
  }

  public function getName() {
    return $this->name;
  }
}
