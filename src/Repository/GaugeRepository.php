<?php

namespace App\Repository;

use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GaugeRepository extends ServiceEntityRepository  {
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
    }

    public function changeGaugeData($newName, $newColor) {
      $this->gauge->setName($newName);
      $this->gauge->setColor($newColor);
      $this->manager->persist($this->gauge);
      $this->manager->flush();
    }

    public function getPreviousValue() {
      $repository = new GaugeChangesRepository($this->registry);
      return $repository->getOldValue($this->gauge->getId());
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
