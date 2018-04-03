<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IssueRoleRepository")
 * @ORM\Table(name="issue_rights")
 */
class IssueRole extends AbstractRoleEntity {
  /**
   * @ORM\ManyToOne(targetEntity="Issue", cascade={"remove"})
   * @ORM\JoinColumn(name="id_issue", referencedColumnName="id")
   */
  private $issue;

  public function setIssue($issue) {
    $this->issue = $issue;
  }

  /**
   * @ORM\ManyToOne(targetEntity="IssueShareHistory")
   * @ORM\JoinColumn(name="id_issue_history", referencedColumnName="id", nullable=true)
   */
  private $issueHistory;

  /**
   * @param IssueShareHistory $history
   */
  public function setIssueHistory($history) {
    $this->issueHistory = $history;
  }

  /** @return boolean */
  public function isIssueHistory() {
    return $this->issueHistory !== null;
  }

  /** @return IssueShareHistory */
  public function getIssueHistory() {
    return $this->issueHistory;
  }
}