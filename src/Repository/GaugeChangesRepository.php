<?php

namespace App\Repository;

use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GaugeChangesRepository  extends ServiceEntityRepository  {
  private $manager;
  private $gaugeChange;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, GaugeChanges::class);
    $this->manager = $registry->getManager();
  }

  // Returns GaugeChange entity for the given ID
  public function getGaugeChange($gaugeChange_id) {
    if(!isset($this->gaugeChange))
      $this->gaugeChange = $this->find($gaugeChange_id);
    return $this->gaugeChange;
  }

  /** Returns date of the newest change done by User in the Issue.
   * @param User $user
   * @param Issue $issue
   * @return \DateTime
   */
  public function getUserNewestChange($user, $issue) {
    $qb = $this->createQueryBuilder('c')
      ->join('App\Entity\Gauge', 'g', 'WITH', 'c.gauge = g.id')
      ->andWhere('c.discard = 0')
      ->andWhere('c.user = :user')
      ->andWhere('g.issue = :issue')
      ->setMaxResults(1)
      ->setParameter('user', $user->getId())
      ->setParameter('issue', $issue->getId())
      ->orderBy('c.time', 'DESC')
      ->getQuery();
    /** @var GaugeChanges[] $result */
    $result = $qb->execute();
    if(sizeof($result) == 0) return null;
    return $result[0]->getRawTime();
  }

  public function getOldValue($gaugeId, $changeId) {
    $qb = $this->createQueryBuilder('c')
      ->select('c.newValue')
      ->where('c.discard = 0')
      ->andWhere('c.gauge = :gauge')
      ->andWhere('c.id <= :change')
      ->setMaxResults(2)
      ->setParameter('gauge', $gaugeId)
      ->setParameter('change', $changeId)
      ->orderBy('c.time', 'DESC')
      ->getQuery();
    $result = $qb->execute();
    if(!isset($result[1]))
      return round($result[0]['newValue']);
    else return round($result[1]['newValue']);
  }

  public function getNewestChange($issue_id) {
    $gauge = $this->getNewestGauge($issue_id);
    $qb = $this->createQueryBuilder('c')
      ->join('App\Entity\Gauge', 'g', 'WITH', 'c.gauge = g.id')
      ->andWhere('c.discard = 0')
      ->andWhere('c.gauge = :latest')
      ->andWhere('g.issue = :issue')
      ->setMaxResults(2)
      ->setParameter('latest', $gauge['gauge'])
      ->setParameter('issue', $issue_id)
      ->orderBy('c.time', 'DESC')
      ->getQuery();
    return $qb->execute();
  }

  /** Selects the id of the most recently changes gauge for the given issue.
   *
   * @param $issue_id - id of the issue
   *
   * @return array - id of the gauge, and id of the gaugeChange
   */
  public function getNewestGauge($issue_id) {
    $qb2 = $this->createQueryBuilder('q')
      ->select('IDENTITY(q.gauge) as gauge, q.id, q.newValue')
      ->join('App\Entity\Gauge', 'g', 'WITH', 'q.gauge = g.id')
      ->where('q.discard = 0')
      ->andWhere('g.issue = :issue')
      ->setMaxResults(1)
      ->setParameter('issue', $issue_id)
      ->orderBy('q.time', 'DESC')
      ->getQuery();
    $latestGauge = $qb2->execute();
    if(sizeof($latestGauge) == 0) return null;
    return
      ["gauge" => $latestGauge[0]['gauge'],
       "change" => $latestGauge[0]['id'],
       "newValue" => $latestGauge[0]['newValue']];
  }

  public function getAllChangesForIssue($issue_id) {
    $qb = $this->createQueryBuilder('q')
      ->join('App\Entity\Gauge', 'g', 'WITH', 'q.gauge = g.id')
      ->where('q.discard = 0')
      ->andWhere('g.issue = :issue')
      ->setParameter('issue', $issue_id)
      ->orderBy('q.time', 'DESC')
      ->getQuery();
    $result = $qb->execute();
    /** @var GaugeChanges $item */
    foreach($result as $key => $item) {
      $old = $this->getOldValue($item->getGauge()->getId(), $item->getId());
      if($old == 1 && $item->getValue() == 1) unset($result[$key]);
      else  $item->setOldValue($old);
    }
    return $result;
  }

  /** Marks the gaugeChange in the DB as discarded.
   *
   * @param $gaugeChange - GaugeChange Entity
   */
  public function gaugeChangeDiscard($gaugeChange) {
    $this->gaugeChange = $gaugeChange;
    $this->gaugeChange->setDiscard();
    $this->manager->flush();
  }

  /** Saves the $text to the latest gauge change in the DB
   * for the given issue.
   *
   * @param $issue_id - Issue Entity ID
   * @param $text - String up to 200 chars
   *
   * @return array - new comment data to draw
   */
  public function gaugeCommentSave($issue_id, $text) {
    $gauge_id = $this->getNewestGauge($issue_id);
    $this->getGaugeChange($gauge_id['change']);
    $this->gaugeChange->setText($text);
    $this->manager->flush();
    return
      ['time' => $this->gaugeChange->getTime(),
        'timeText' => $this->gaugeChange->getTimeText(),
       'value' => $this->gaugeChange->getValue(),
       'oldValue' => $this->getOldValue($gauge_id['gauge'], $this->gaugeChange->getId()),
       'text' => $this->gaugeChange->getText(),
        'user' => $this->gaugeChange->getUser(),
       'gauge' =>
         ['color' => $this->gaugeChange->getGauge()->getColor(),
          'name' => $this->gaugeChange->getGauge()->getName()]];
  }

}