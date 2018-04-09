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
    $replace = [ 'ě' => 'e', 'š' => 's', 'č' => 'c', 'ř' => 'r', 'ž' => 'z',
                 'ý' => 'y', 'á' => 'a', 'í' => 'i', 'é' => 'e', 'ú' => 'u',
                 'ů' => 'u', 'ď' => 'd', 'ť' => 't', 'ň' => 'n', 'ó' => 'o',
                 'Ě' => 'E', 'Š' => 'S', 'Č' => 'C', 'Ř' => 'R', 'Ž' => 'Z',
                 'Ý' => 'Y', 'Á' => 'A', 'Í' => 'I', 'É' => 'E', 'Ú' => 'U',
                 'Ů' => 'U', 'Ď' => 'D', 'Ť' => 'T', 'Ň' => 'N', 'Ó' => 'O' ];
    $changed = str_replace(',', '', strtolower(str_replace(array_keys($replace), $replace, $newName)));
    $replaced = preg_replace("/[^A-Za-z ]/", '', $changed);
    $this->linkName = str_replace(' ', '-', strtolower(trim($replaced)));
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
