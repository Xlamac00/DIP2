<?php

namespace App\Repository;

use App\Entity\Issue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class IssueRepository extends ServiceEntityRepository {
  private $registry;
  private $manager;
  private $issue;

    public function __construct(RegistryInterface $registry) {
        parent::__construct($registry, Issue::class);
      $this->registry = $registry;
      $this->manager = $registry->getManager();
    }

    // Returns Issue entity for the given ID
    public function getIssue($issue_id, $forceFind = false) {
      if(!isset($this->issue) || $forceFind === true)
        $this->issue = $this->find($issue_id);
      return $this->issue;
    }

    // Returns Issue entity for the given link
    public function getIssueByLink($link) {
      if(!isset($this->issue))
        $this->issue = $this->findOneBy(["link" => $link]);
      return $this->issue;
    }

    public function getNumberOfGauges($issue_id = null) {
      if($issue_id === null) $issue_id = $this->issue->getId();
      $qb = $this->createQueryBuilder('q')
        ->select('DISTINCT g.id')
        ->from('App\Entity\Gauge', 'g')
        ->andWhere('g.issue = :issue')
        ->setParameter('issue', $issue_id)
        ->getQuery();
      $result = $qb->execute();
      return sizeof($result);
    }

  /**Changes the value of gauge in the issue.
   *
   * @param $gaugeIndex - index of the gauge in the issue
   *   (gauges have to be ordered to find its real id!)
   * @param $value - new value of the issue
   *
   * @return array - new gauge value from db or "error" text
   */
    public function gaugeValueChange($gaugeIndex, $value) {
      foreach($this->issue->getGauges() as $key => $data) { // all gauges in the issue
        if($key == $gaugeIndex) { // correct gauge (ordered)
          $gauge = new GaugeRepository($this->registry);
          $gauge->getGauge($data->getId());
          $oldValue = $gauge->getPreviousValue();
          $newValue = $gauge->gaugeValueChange($value);
          $gauge->gaugeValueLog($value);
          return
            ['color' => $data->getColor(),
             'oldValue' => round($oldValue),
             'newValue' => round($newValue)];
        }
      }
      return ["error"];
    }
}
