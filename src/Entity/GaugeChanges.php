<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GaugeChangesRepository")
 * @ORM\Table(name="gauge_changes")
 */
class GaugeChanges {
  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="Gauge", inversedBy="changes", cascade={"remove"})
   * @ORM\JoinColumn(name="id_gauge", referencedColumnName="id", nullable=true, onDelete="SET NULL")
   */
  private $gauge;

  /**
   * @ORM\Column(type="decimal", precision=4, scale=1)
   */
  private $newValue;

  // Not to be saved in the DB, must be computed every time needed
  private $oldValue;

  /**
   * @ORM\Column(type="string", length=200, nullable=true)
   */
  private $text;

  /**
   * @ORM\ManyToOne(targetEntity="User")
   * @ORM\JoinColumn(name="user", referencedColumnName="id")
   */
  private $user;

  /**
   * @ORM\Column(type="boolean")
   */
  private $discard;

  /**
   * @var DateTime
   * @ORM\Column(type="datetime")
   */
  private $time;

  public function getId() {
    return $this->id;
  }

  public function getOldValue() {
    if($this->oldValue <= 2) return 0;
    else return $this->oldValue;
    return $this->oldValue;
  }

  public function setOldValue($value) {
    $this->oldValue = $value;
  }

  public function getValue() {
    $v = round($this->newValue);
    if($v <= 2) return 0;
    else return $v;
    return $v;
  }

  public function getGauge(): Gauge {
    return $this->gauge;
  }

  public function setGauge(Gauge $gauge) {
    $this->gauge = $gauge;
  }

  /**
   * @param $newValue
   */
  public function setValues($newValue) {
    $this->newValue = $newValue;
    $this->time = new \DateTime("now");
    $this->discard = false;
    $this->user = 1;
    $this->text = NULL;
  }

  public function getTime() {
    return $this->time->format('d.m.Y \v H:i');
  }

  public function getRawTime() {
    return $this->time;
  }

  public function getTimeText() {
    $now = new DateTime;
    $diff = $now->diff($this->time);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
      'y' => 'y',
      'm' => 'mo',
      'w' => 'w',
      'd' => 'd',
      'h' => 'h',
      'i' => 'min',
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
    return implode(', ', $string).' ago';
  }

  public function setText($text) {
    $this->text = $text;
  }

  public function getText() {
    return $this->text;
  }

  public function getUser() {
    return $this->user;
  }

  public function setUser($user) {
    $this->user = $user;
  }

  public function setDiscard() {
    $this->discard = true;
  }

  public function isDiscarded() {
    return $this->discard;
  }

  public function toString() {
    return "GaugeChange: <br><br>".
            "Gauge: ".$this->gauge->getId()."<br><br>".
            "Time: ".$this->time->format('Y-m-d H:i:s').
            "<br>Text: ".$this->text.
            "<br>NewValue: ".$this->newValue;
  }
}
