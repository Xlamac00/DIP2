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

  /** Indicates that user gained access via board-link (isBoardHistory) and it was changed - should be affected
   * by any other changes to board-link until set to null again.
   * @ORM\Column(type="string", length=24, nullable=true)
   */
  private $oldBoardRole;

  public function setOldBoardRole($role) {
    $this->oldBoardRole = $role;
  }

  public function getOldBoardRole() {
    return $this->oldBoardRole;
  }

  public function isOldBoardRole() {
    return $this->oldBoardRole !== null;
  }
}