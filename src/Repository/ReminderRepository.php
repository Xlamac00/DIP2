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
}
