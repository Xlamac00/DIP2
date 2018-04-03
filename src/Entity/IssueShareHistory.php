<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity()
 * @ORM\Table(name="issue_history")
 */
class IssueShareHistory extends AbstractHistoryRoleEntity {
  /**
   * @ORM\ManyToOne(targetEntity="Issue", cascade={"remove"})
   * @ORM\JoinColumn(name="id_issue", referencedColumnName="id")
   */
  private $entity;

  public function setIssue($issue) {
    $this->entity = $issue;
  }

  /**
   * @ORM\ManyToOne(targetEntity="BoardShareHistory")
   * @ORM\JoinColumn(name="id_board_history", referencedColumnName="id")
   */
  private $boardHistory;

  /** @param BoardShareHistory $boardHistory */
  public function setBoardHistory($boardHistory) {
    $this->boardHistory = $boardHistory;
  }

  public function getBoardHistory() {
    return $this->boardHistory;
  }
}