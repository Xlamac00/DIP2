<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReminderHistoryRepository")
 * @ORM\Table(name="reminder_history")
 */
class ReminderHistory {
  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\ManyToOne(targetEntity="Issue", cascade={"remove"})
   * @ORM\JoinColumn(name="id_issue", referencedColumnName="id")
   */
  private $issue;

  /**
   * @ORM\ManyToOne(targetEntity="User", cascade={"remove"})
   * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
   */
  private $user;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $time;

  public function setTime() {
    $this->time = new \DateTime("now");
  }

  /** @param User $user */
  public function setUser($user) {
    $this->user = $user;
  }

  /** @param Issue $issue */
  public function setIssue($issue) {
    $this->issue = $issue;
  }

  public function getTime() {
    return $this->time;
  }
}