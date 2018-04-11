<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface, \Serializable {
  /**
   * @ORM\Column(type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=24)
   */
  private $username;

  /**
   * @ORM\Column(type="string", length=24)
   */
  private $link;

  /**
   * @ORM\Column(type="string", length=64, unique=true, nullable=true)
   */
  private $email;

  /**
   * Email for anonymous users who got access via email invitation
   * @ORM\Column(type="string", length=64, nullable=true)
   */
  private $anonymousEmail;

  /**
   * @ORM\Column(type="string", length=7)
   */
  private $color;

  /**
   * @ORM\Column(name="id_google", type="string", length=24, nullable=true)
   */
  private $googleId;

  /**
   * @ORM\Column(name="img_google", type="string", length=128, nullable=true)
   */
  private $googleImg;

  /**
   * @ORM\Column(name="is_active", type="boolean")
   */
  private $isActive;

  /** Variables set in the authenticator if the user is granted permission to see certain pages.  */
  private $permissionPageId;
  private $permissionRole;

  public function setPagePermission($pageId, $role) {
    $this->permissionPageId = $pageId;
    $this->permissionRole = $role;
  }

  public function __construct() {
    $this->isActive = true;
    $this->email = null;
    $this->googleId = null;
    $this->googleImg = null;
    $this->anonymousEmail = null;
  }

  public function getId() {
    return $this->id;
  }
  public function setUsername($name) {
    $this->username = $name;
  }
  public function setGoogleId($googleId) {
    $this->googleId = $googleId;
  }
  public function setLink($link) {
    $this->link = $link;
  }
  public function setEmail($email) {
    $this->email = $email;
  }
  public function setAnonymousEmail($email) {
    $this->anonymousEmail = $email;
  }
  public function setImageLink($image) {
    $this->googleImg = $image;
  }
  public function setColor($color) {
    $this->color = $color;
  }
  public function getPassword() {
    return null;
  }
  public function getEmail() {
    return $this->email;
  }
  public function getGoogleId() {
    return $this->googleId;
  }
  public function getImageLink() {
    return $this->googleImg;
  }
  public function getColor() {
      return $this->color;
  }
  public function getAnonymousEmail() {
    return $this->anonymousEmail;
  }

  // Username is unique 20 char string
  // For anonymous users these are random 20 characters
  public function getUsername() {
    return $this->username;
  }

  public function getUniqueLink() {
    if($this->isAnonymous())
      return $this->link;
    else
      return $this->googleId;
  }

  public function getAnonymousLink() {
    return $this->link;
  }

  public function isAnonymous() {
    return $this->googleId === NULL;
  }

  public function getSalt() {
    return null;
  }

  public function getRoles() {
    if($this->isAnonymous())
      return array('ROLE_ANONYMOUS');
    else
      return array('ROLE_USER', 'ROLE_LOGGED');
  }

  public function eraseCredentials() {
  }

  /** @see \Serializable::serialize() */
  public function serialize() {
    return serialize(array(
      $this->id,
      $this->username,
      $this->link,
      $this->googleId,
    ));
  }

  /** @see \Serializable::unserialize() */
  public function unserialize($serialized) {
    list (
      $this->id,
      $this->username,
      $this->link,
      $this->googleId,
      ) = unserialize($serialized);
  }
}
