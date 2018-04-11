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
  private $shareLink;

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
  private $entityObject;

  public function setEmail($email) {
    $this->email = $email;
    $this->time = new \DateTime("now");
    $this->shareLink = md5($this->time->format('U').$this->email);
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
    return 'u/'.$this->entityObject->getPageId().'/'.$this->entityObject->getLink().'?'.$this->shareLink;
  }

}
