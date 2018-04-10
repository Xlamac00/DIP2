<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity()
 * @ORM\Table(name="bug")
 */
class Bug {

  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=256)
   */
  private $text;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $time;

  /**
   * @ORM\ManyToOne(targetEntity="User")
   * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
   */
  private $user;

  public function setText($text) {
    $this->text = $text;
  }

  public function setTime() {
    $this->time = new \DateTime("now");
  }

  public function setUser($user) {
    $this->user = $user;
  }

  public function getUser() {
    return $this->user;
  }

  public function getText() {
    return $this->text;
  }
}
