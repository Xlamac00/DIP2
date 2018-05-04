<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GaugeRepository")
 * @ORM\Table(name="gauge")
 */
class Gauge {

  public function __construct() {
    $this->changes = new ArrayCollection();
  }

  /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="Issue", inversedBy="gauges")
   * @ORM\JoinColumn(name="id_issue", referencedColumnName="id", nullable=true, onDelete="SET NULL")
   */
  private $issue;

  /**
   * @var User $userEdit
   * @ORM\ManyToOne(targetEntity="User")
   * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
   */
  private $userEdit;

  public function getIssue() {
    return $this->issue;
  }

  public function setIssue($issue_id) {
    $this->issue = $issue_id;
  }

  /** @param User $user */
  public function bindUserToGauge($user) {
    $this->userEdit = $user;
  }

  public function hasBindUser() {
    return $this->userEdit !== null;
  }

  public function getBindUserName() {
    if($this->userEdit instanceof  User)
      return $this->userEdit->getUsername();
    else return '';
  }

  public function getBindUserMail() {
    if($this->userEdit instanceof  User) {
      if($this->userEdit->isAnonymous() && $this->userEdit->getAnonymousEmail() !== null)
        return explode('@', $this->userEdit->getAnonymousEmail())[0]."@...";
      else
        return $this->userEdit->getUsername();
    }
    else return '';
  }

  /**
   * @ORM\OneToMany(targetEntity="GaugeChanges", mappedBy="gauge")
   */
  private $changes;

  /**
   * @return Collection|GaugeChanges[]
   */
  public function getChanges()  {
    return $this->changes;
  }

  /**
   * @ORM\Column(type="string", length=100)
   */
  private $name;

  /**
   * @ORM\Column(type="string", length=16)
   */
  private $color;

  /**
   * @ORM\Column(type="integer")
   */
  private $value;

/** Order by which the gauges are shown in the issue
 * @ORM\Column(type="integer")
 */
  private $position;

  public function getPosition() {
    return $this->position;
  }

  public function setPosition($position) {
    $this->position = $position;
  }

  /**
   * @return mixed
   */
  public function getColor() {
    return $this->color;
  }

  public function getColorName() {
    return substr($this->color, 1);
  }

  public function setColor($color) {
    $this->color = ($color[0] == '#' ? '':'#').$color;
  }

  /**
   * @return mixed
   */
  public function getValue() {
    return $this->value;
  }

  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  public function toString() {
    return "Gauge: <br><br>".
            "Id: ".$this->id."<br>".
            "Name: ".$this->name;
  }
}
