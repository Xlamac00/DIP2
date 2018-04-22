<?php

namespace App\Repository;

use App\Entity\Deadline;
use App\Entity\Issue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

  /** Returns all active deadlines for given Issue.
   * @param Issue $issue
   * @return Deadline[]
   */
  public function getDeadlinesForIssue($issue) {
    if(!isset($this->deadline)) {
      $qb = $this->createQueryBuilder('d')
        ->where('d.issue = :issue')
        ->andWhere('d.end > :yesterday')
        ->setParameter('issue', $issue->getId())
        ->setParameter('yesterday', new \DateTime('-2 days'), \Doctrine\DBAL\Types\Type::DATETIME)
        ->orderBy('d.end', 'ASC')
        ->getQuery();
      $this->deadline = $qb->execute();
    }
    return $this->deadline;
  }
}