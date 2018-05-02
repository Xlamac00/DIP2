<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeadlineRepository")
 * @ORM\Table(name="deadline")
 */
class Deadline {
  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @var string $text
   * @ORM\Column(type="string", length=256)
   */
  private $text;

  /**
   * @var DateTime $start
   * @ORM\Column(type="date")
   */
  private $start;

  /**
   * @var DateTime $end
   * @ORM\Column(type="date")
   */
  private $end;

  /**
   * @var Issue $issue
   * @ORM\ManyToOne(targetEntity="Issue")
   * @ORM\JoinColumn(name="id_issue", referencedColumnName="id")
   */
  private $issue;

  /**
   * @var Gauge $gauge
   * @ORM\ManyToOne(targetEntity="Gauge")
   * @ORM\JoinColumn(name="id_gauge", referencedColumnName="id", nullable=true, onDelete="CASCADE")
   */
  private $gauge;

  /** @param DateTime $start */
  public function setStart($start) {
    $this->start = $start;
  }

  /** @param string $text */
  public function setText($text) {
    $this->text = $text;
  }

  /** @param DateTime $end */
  public function setEnd($end) {
    $this->end = $end;
  }

  /** @param Issue $issue */
  public function setIssue($issue) {
    $this->issue = $issue;
  }

  /** @param Gauge $gauge */
  public function setGauge($gauge) {
    $this->gauge = $gauge;
  }

  public function getId() {
    return $this->id;
  }

  public function getText() {
    return $this->text;
  }

  public function getStart() {
    return $this->start->format('d. m.');
  }

  public function getStartPicker() {
    return $this->start->format('d/m/Y');
  }

  public function getEnd() {
    return $this->end->format('d. m.');
  }

  public function getEndPicker() {
    return $this->end->format('d/m/Y');
  }

  public function getColor() {
    if($this->gauge === null || $this->gauge->getColor() === null)
      return '#666';
    else
      return $this->gauge->getColor();
  }

  public function getName() {
    if($this->gauge === null)
      return $this->issue->getName();
    else
      return $this->gauge->getName();
  }

  public function getGaugeId() {
    if($this->gauge !== null)
      return $this->gauge->getId();
    else return 'issue';
  }

  public function getDaysLeft() {
    return ((new DateTime("now - 1 day"))->diff($this->end))->format('%a');
  }

  public function getPercentage() {
    $total = $this->start->diff($this->end)->format('%a');
    $now = (new DateTime("now - 1 day"))->diff($this->end)->format('%a');
    if($total == 0) return 100;
    return 100-(($now*100)/$total);
  }

  /** @return Issue */
  public function getIssue() {
    return $this->issue;
  }

}
