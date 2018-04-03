<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @MappedSuperclass */
abstract class AbstractLinkableEntity {
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
  protected $linkName;

  /**
   * @ORM\Column(type="string", length=8, unique=true)
   */
  protected $linkId;

  public function getId() {
    return $this->id;
  }

  public function setName($newName) {
    $this->name = ucfirst(strtolower($newName));
    $changed = iconv('utf-8', 'ascii//TRANSLIT', $newName); // convert á to a, etc..
    $this->linkName = str_replace(' ', '-', strtolower($changed));
  }

  protected function generateLinks() {
    $this->linkId = substr(md5(uniqid(mt_rand(), true)), 0,8);
  }

  public function getName() {
    return $this->name;
  }

  public function getLink() {
    return $this->linkName;
  }

  public function getPageId() {
    return $this->linkId;
  }

  /** Returns the rest of the URL, but the first part has to be added in concrete entity.
   * (eg in Board: 'b/'.parent::getUrl();)
   */
  protected function getUrl() {
    return $this->linkId."/".$this->linkName;
  }
}
