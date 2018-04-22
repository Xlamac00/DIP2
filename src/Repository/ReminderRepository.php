<?php

namespace App\Repository;

use App\Entity\Reminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ReminderRepository extends ServiceEntityRepository {
  /** @var  Reminder */
  private $reminder;
  private $manager;
  private $registry;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, Reminder::class);
    $this->manager = $registry->getManager();
    $this->registry = $registry;
  }

  public function getReminderByIssue($issue) {
    if(!isset($this->reminder)) {
      $this->reminder = $this->findOneBy(["issue" => $issue]);
    }
    return $this->reminder;
  }

  /** Returns all Reminders that are set (=='true') on given day
   * @param integer $day - number from 0-6 with day in the week (0=monday)
   * @return Reminder[]
   */
  public function getReminderByDay($day) {
    $reminder = $this->findAll();
    /** @var Reminder $data */
    foreach ($reminder as $key => $data) { // remove all Issue that dont have set $day to true
      if($data->getDays()[$day] === 'false')
        unset($reminder[$key]);
    }
    return $reminder;
  }
}
