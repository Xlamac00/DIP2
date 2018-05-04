<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserShareRepository")
 * @ORM\Table(name="user_share")
 */
class UserShare  extends AbstractBasicRoleEntity {

  /**
   * @ORM\Column(type="string", length=64)
   */
  protected $shareLink;

  /**
   * @ORM\Column(type="datetime")
   */
  private $time;

  /**
   * @ORM\Column(type="string", length=64)
   */
  private $email;

  /**
   * @ORM\Column(type="string", length=8)
   */
  private $entity;

  /** @var  AbstractSharableEntity */
  protected $entityObject;

  /**
   * @ORM\ManyToOne(targetEntity="Gauge")
   * @ORM\JoinColumn(name="id_gauge", referencedColumnName="id", nullable=true, onDelete="SET NULL")
   */
  private $gauge;

  public function setEmail($email) {
    $this->email = $email;
    $this->time = new \DateTime("now");
    $this->shareLink = md5($this->time->format('U').$this->email);
    $this->gauge = null;
  }

  /** @param Gauge $gauge */
  public function setGauge($gauge) {
    $this->gauge = $gauge;
  }

  public function getGauge() {
    return $this->gauge;
  }

  /**
   * @param AbstractSharableEntity $entity
   */
  public function setEntity($entity) {
    $this->entityObject = $entity;
    $this->entity = $entity->getPageId();
  }

  public function getEmail() {
    return $this->email;
  }

  public function getUrl() {
    $start = $this->gauge === null ? 'u' : 'g';
    return $start.'/'.$this->entityObject->getPageId().'/'.$this->entityObject->getLink().'?'.$this->shareLink;
  }

}
