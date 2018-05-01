<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NotificationRepository")
 * @ORM\Table(name="notification")
 */
class Notification {
  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=512)
   */
  private $text;

  /**
   * @ORM\Column(type="string", length=256)
   */
  private $url;

  /**
   * @ORM\ManyToOne(targetEntity="User")
   * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
   */
  private $user;

  /**
   * @ORM\ManyToOne(targetEntity="User")
   * @ORM\JoinColumn(name="id_creator", referencedColumnName="id")
   */
  private $creator;

  /**
   * @var DateTime $shown
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $shown;

  /**
   * @var DateTime $created
   * @ORM\Column(type="datetime")
   */
  private $created;

  public function setDate() {
    $this->created = new DateTime("now");
  }

  public function setShown() {
    $this->shown = new DateTime("now");
  }

  /** @param User $creator */
  public function setCreator($creator) {
    $this->creator = $creator;
  }

  /** @param UserInterface $user */
  public function setUser($user) {
    $this->user = $user;
  }

  public function setText($text) {
    $this->text = $text;
  }

  public function setUrl($url) {
    $this->url = $url;
  }

  public function getText() {
    return $this->text;
  }

  public function getLink() {
    return '../../'.$this->url;
  }

  public function getDate() {
    return $this->created->format('d. m. Y \v H:i');
  }

  public function isShown() {
    return $this->shown !== null;
  }

  public function getShown() {
    return $this->shown->format('d. m. Y \v H:i');
  }

  public function getDateText() {
    $now = new DateTime;
    $diff = $now->diff($this->created);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
      'y' => 'y',
      'm' => 'mo',
      'w' => 'w',
      'd' => 'd',
      'h' => 'h',
      'i' => 'm',
      's' => 's',
    );
    foreach ($string as $k => &$v) {
      if ($diff->$k) {
        $v = $diff->$k.''.$v;
      } else {
        unset($string[$k]);
      }
    }

    $string = array_slice($string, 0, 1);
    return implode(', ', $string).'<br>ago';
  }
}
