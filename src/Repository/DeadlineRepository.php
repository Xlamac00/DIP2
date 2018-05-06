<?php

namespace App\Repository;

use App\Entity\Deadline;
use App\Entity\Gauge;
use App\Entity\Issue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Constraints\DateTime;

class DeadlineRepository extends ServiceEntityRepository {
  /** @var  Deadline */
  private $deadline;
  private $manager;
  private $registry;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, Deadline::class);
    $this->manager = $registry->getManager();
    $this->registry = $registry;
  }

  /** Gets one deadline from db by db id.
   * @param integer $db_id
   * @return Deadline
   */
  public function getDeadlineById($db_id) {
    /** @var Deadline $deadline */
    $deadline = $this->find($db_id);
    return $deadline;
  }

  /** Returns one deadline that
   * @param Issue $issue - Issue in which the Deadline should be
   * @param string|null $gaugeId - null if it should be deadline for whole Issue, my db id otherwise
   *
   * @return Deadline
   */
  public function getDeadlineByIssue($issue, $gaugeId) {
    /** @var Deadline $dl */
    $dl = $this->findOneBy(array('issue' => $issue->getId(), 'gauge' => $gaugeId));
    return $dl;
  }

  /** Returns all active deadlines for given Issue.
   * @param Issue $issue
   * @return Deadline[]
   */
  public function getDeadlinesForIssue($issue) {
      $qb = $this->createQueryBuilder('d')
        ->where('d.issue = :issue')
        ->andWhere('d.end > :yesterday')
        ->setParameter('issue', $issue->getId())
        ->setParameter('yesterday', new \DateTime('-2 days'), \Doctrine\DBAL\Types\Type::DATETIME)
        ->orderBy('d.end', 'ASC')
        ->getQuery();
      return $qb->execute();
  }

  /** Returns all active deadlines for given User
   * @param User $user - currently logged user
   * @return array
   */
  public function getDeadlinesForUser($user) {
    $qb = $this->createQueryBuilder('d')
      ->join('App\Entity\Gauge', 'g', 'WITH', 'd.gauge = g.id')
      ->where('g.userEdit = :user')
      ->andWhere('d.end > :yesterday')
      ->setParameter('user', $user->getId())
      ->setParameter('yesterday', new \DateTime('-2 days'), \Doctrine\DBAL\Types\Type::DATETIME)
      ->orderBy('d.end', 'ASC')
      ->getQuery();
    /** @var Deadline[] $items */
    $items = $qb->execute();
//    die(sizeof($items)."A");
    $issues = array();
    foreach ($items as $deadline) {
      $board = $deadline->getIssue()->getBoard();
      try{$board->getName();} // try if board is not deleted
      catch (EntityNotFoundException $e){continue;}
      if(!isset($issues[$deadline->getIssue()->getId()])) {
        $issue = array();
        $issue['deadlines'] = array();
        $issue['name'] = $board->getName().": ".$deadline->getIssue()->getName();
        $issue['url'] = $deadline->getIssue()->getUrl();
        $issue['color'] = $board->getBackground();
        $issues[$deadline->getIssue()->getId()] = $issue;
      }
    }
    foreach ($items as $deadline) {
      if(isset($issues[$deadline->getIssue()->getId()])) {
        $issues[$deadline->getIssue()->getId()]['deadlines'][] = $deadline;
      }
    }
    return $issues;

  }
}