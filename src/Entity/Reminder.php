<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReminderRepository")
 * @ORM\Table(name="reminder")
 */
class Reminder {

  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=1024)
   */
  private $text;

  /**
   * @ORM\Column(type="boolean")
   */
  private $sendAnyway;

  /**
   * @ORM\ManyToOne(targetEntity="Issue")
   * @ORM\JoinColumn(name="id_issue", referencedColumnName="id")
   */
  private $issue;

  /**
   * @ORM\Column(type="array")
   */
  private $days;

  /**
   * @ORM\Column(type="array")
   */
  private $users;

  /** @param Issue $issue */
  public function setIssue($issue) {
    $this->issue = $issue;
  }

  public function setText($text) {
    $this->text = $text;
  }

  /** @param Boolean $bool */
  public function setSendAnyway($bool) {
    $this->sendAnyway = $bool;
  }

  public function setDays($array) {
    $this->days = $array;
  }

  public function setUsers($array) {
    $this->users = $array;
  }

  public function getText() {
    return $this->text;
  }

  public function canSendAnyway() {
    return $this->sendAnyway === true;
  }

  public function getUsers() {
    return $this->users;
  }

  public function getDays() {
    return $this->days;
  }
}
