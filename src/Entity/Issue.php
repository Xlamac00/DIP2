<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\IssueRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
 * @ORM\Table(name="issue")
 */
class Issue extends AbstractSharableEntity {

  public function __construct() {
    parent::__construct();
    $this->gauges = new ArrayCollection();
    $this->oldShareRights = null;
  }

  /**
   * @ORM\ManyToOne(targetEntity="Board", inversedBy="issues")
   * @ORM\JoinColumn(name="id_board", referencedColumnName="id")
   */
  private $board;

  public function setBoard($board_id) {
    $this->board = $board_id;
  }

  /** @return Board */
  public function getBoard() {
    return $this->board;
  }

  /**
   * @ORM\OneToMany(targetEntity="Gauge", mappedBy="issue")
   * @ORM\OrderBy({"position" = "ASC"})
   */
  private $gauges;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $deletedAt;

  public function setDeletedAt($deletedAt) {
    $this->deletedAt = $deletedAt;
  }

  /** Variable with array of users that contributed to the issue.
   * Has to be manually set (eg. from boardrepository) */
  private $activeUsers;

  /** Set if the sharing was changed for this Issue only. While this is not null,
   * its not affected by Board sharing rights changes. When its set to its default value,
   * oldShareRights is set to null and it inherits from its parent Board once again.
   * @ORM\Column(type="string", length=32, nullable=true)
   */
  private  $oldShareRights;

  public function getOldShareRights() {
    return $this->oldShareRights;
  }

  public function setOldShareRights($newRight = null) {
    $this->oldShareRights = $newRight;
  }

  /**
   * @return Collection|Gauge[]
   */
  public function getGauges()  {
    return $this->gauges;
  }

  public function getGaugesSorted() {
    return usort($this->gauges, array($this, "compare"));
  }

  /** Compares gauges to sort them by position.
   * @param Gauge $a
   * @param Gauge $b
   * @return int
   */
  private function compare($a, $b) {
    return strcmp($a->getPosition(), $b->getPosition());
  }

  /** Array of few latest GaugeChanges */
  private $gaugeCommentLatest;

  /** @var int - number of GaugeChanges for the issue */
  private $gaugeCommentsCount;

  public function getLatestGaugeComments() {
    return $this->gaugeCommentLatest;
  }

  public function getCountGaugeComments() {
    return $this->gaugeCommentsCount;
  }

  public function setLatestGaugeComments($comments) {
    $this->gaugeCommentLatest = $comments;
  }

  public function setCountGaugeComments($count) {
    $this->gaugeCommentsCount = $count;
  }
  /**
   * @param Gauge $gauge
   */
  public function setGauge($gauge) {
    $this->gauges[] = $gauge;
  }

  public function getUrl() {
    return 'i/'.parent::getUrl();
  }

  /**
   * @param array $usersArray
   */
  public function setActiveUsers($usersArray) {
    $this->activeUsers = $usersArray;
  }

  public function getActiveUsers() {
    return $this->activeUsers;
  }
}
