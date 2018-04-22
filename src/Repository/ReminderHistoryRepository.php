<?php

namespace App\Repository;

use App\Entity\Issue;
use App\Entity\ReminderHistory;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ReminderHistoryRepository extends ServiceEntityRepository {
  private $manager;
  private $registry;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, ReminderHistory::class);
    $this->manager = $registry->getManager();
    $this->registry = $registry;
  }

  /** Returns the date of the last reminder send to User for given Issue.
   * @param User $user
   * @param Issue $issue
   * @return DateTime
   */
  public function getLastReminderDate($user, $issue) {
    /** @var ReminderHistory $history */
    $history = $this->findOneBy(['user' => $user, 'issue' => $issue], ['time' => 'DESC']);
    if($history === null) return null;
    return $history->getTime();
  }
}
