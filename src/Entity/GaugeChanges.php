<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

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
   * @ORM\JoinColumn(name="gauge_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
   */
  private $gauge;

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

  /**
   * @ORM\Column(type="decimal", precision=4, scale=1)
   */
  private $newValue;

  public function getValue() {
    return round($this->newValue);
  }

  // Not to be saved in the DB, will be computed every time needed
  private $oldValue;

  public function getOldValue() {
    return $this->oldValue;
  }

  public function setOldValue($value) {
    $this->oldValue = $value;
  }

  /**
   * @ORM\Column(type="datetime")
   */
  private $time;

  public function getTime() {
    return $this->time->format('H:i d.m.Y');
  }

  /**
   * @ORM\Column(type="string", length=200, nullable=true)
   */
  private $text;

  public function setText($text) {
    $this->text = $text;
  }

  public function getText() {
    return $this->text;
  }

  /**
   * @ORM\Column(type="integer")
   */
  private $user;

  /**
   * @ORM\Column(type="boolean")
   */
  private $discard;

  public function setDiscard() {
    $this->discard = true;
  }

  public function toString() {
    return "GaugeChange: <br><br>".
            "Gauge: ".$this->gauge->getId()."<br><br>".
            "Time: ".$this->time->format('Y-m-d H:i:s').
            "<br>NewValue: ".$this->newValue;
  }
}
