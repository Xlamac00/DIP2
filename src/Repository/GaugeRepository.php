<?php

namespace App\Repository;

use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GaugeRepository extends ServiceEntityRepository  {
  /** @var  Gauge */
  private $gauge;
  private $manager;
  private $registry;

  public function __construct(RegistryInterface $registry) {
      parent::__construct($registry, Gauge::class);
      $this->manager = $registry->getManager();
      $this->registry = $registry;
  }

  // Returns Gauge entity for the given ID
  public function getGauge($gauge_id) {
    if(!isset($this->gauge))
      $this->gauge = $this->find($gauge_id);
    return $this->gauge;
  }

  /** Returns one Gauge and checks if its in given Issue
   * @param integer $gaugeId - db id of gauge
   * @param Issue $issue - issue
   *
   * @return Gauge
   */
  public function getGaugeInIssue($gaugeId, $issue) {
    /** @var Gauge $gauge */
    $gauge = $this->findOneBy(['issue' => $issue->getId(), 'id' => $gaugeId]);
    return $gauge;
  }

  /** Returns all Gauges in Issue
   * @param Issue $issue - issue
   *
   * @return Gauge[]
   */
  public function getGaugesInIssue($issue) {
    /** @var Gauge[] $gauge */
    $gauge = $this->findBy(['issue' => $issue->getId()]);
    return $gauge;
  }

  /** Returns if the Issue and User has set rights only for one-gauge-edit.
   * If return true, user has ISSUE_ROLE_GAUGE role, else IssueRole rights have to be checked.
   * @param Issue $issue
   * @param User $user
   * @return boolean
   */
  public function isGaugeRightForIssue($user, $issue) {
    $result = $this->findBy(array('issue' => $issue->getId(), 'user' => $user->getId()));
    return $result !== null;
  }

  /** Creates one-gauge-edit-only rights for the user
   * @param Gauge $gauge
   * @param User $user
   */
  public function bindUserWithGauge($gauge, $user) {
    $gauge->bindUserToGauge($user);
    $this->manager->flush();
  }

  /** Returns array with 1 for each Gauge (ordered by gauge position) which can be edited by the user.
   * 1 means he can edit Gauge, 0 he cannot
   * @param Issue $issue
   * @param User $user
   * @return integer[]
   */
  public function getBoundGauges($issue, $user) {
    $gauges = $this->findBy(array('issue' => $issue->getId()));
    $result = array();
    /** @var Gauge $gauge */
    foreach($gauges as $gauge) {
      if($gauge->getBindUserName() == $user->getUsername())
        $result[] = 1;
      else
        $result[] = 0;
    }
    return $result;
  }

  public function setGauge($gauge) {
    $this->gauge = $gauge;
  }

  // Changes value of the gauge to the new one
  public function gaugeValueChange($newValue) {
    $value = round($newValue, 1);
    $this->gauge->setValue($value);
    $this->manager->flush();
    return $value;
  }

  // Logs the change of the value in gauge
  public function gaugeValueLog($newValue, $userId) {
    $repository = new UserRepository($this->registry);
    $user = $repository->loadUserById($userId);
    $change = new GaugeChanges();
    $change->setGauge($this->gauge);
    $change->setValues($newValue);
    $change->setUser($user);
    $this->manager->persist($change);
    $this->manager->flush();
    return $change->getId();
  }

  /** Initiates new Gauge to value 1.
   * Used only when creating new Gauge.
   *
   * @param Gauge $gauge
   * @param User $user
   */
  public function gaugeValueInit($gauge, $user) {
    $change = new GaugeChanges();
    $change->setGauge($gauge);
    $change->setValues(1);
    $change->setUser($user);
    $this->manager->persist($change);
    $this->manager->flush();
  }

  public function changeGaugeName($newName) {
    $this->gauge->setName($newName);
    $this->manager->persist($this->gauge);
    $this->manager->flush();
  }

  public function changeGaugeColor($newColor) {
    $this->gauge->setColor($newColor);
    $this->manager->persist($this->gauge);
    $this->manager->flush();
  }

  public function getPreviousValue($changeId) {
    $repository = new GaugeChangesRepository($this->registry);
    return $repository->getOldValue($this->gauge->getId(), $changeId);
  }

  public function gaugeValueDiscard($issue_id) {
    $repository = new GaugeChangesRepository($this->registry);
    $change = $repository->getNewestChange($issue_id);
    $this->gauge = $change[1]->getGauge();
    $this->gaugeValueChange($change[1]->getValue()); //save old value as current value
    $repository->gaugeChangeDiscard($change[0]); // discard the latest change in log
    return
      ['newValue' => $change[1]->getValue(),
       'position' => $change[1]->getGauge()->getPosition()];
  }
}
