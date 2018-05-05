<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class NotificationRepository extends ServiceEntityRepository {
  /** @var  Notification[] */
  private $notification;
  private $manager;
  private $registry;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, Notification::class);
    $this->manager = $registry->getManager();
    $this->registry = $registry;
  }

  /** Returns all unread notifications for given user (displayed on all pages).
   * @param User $user
   *
   * @return Notification|array
   */
  public function getUnreadNotifications($user) {
    if(!isset($this->notification)) {
      $qb = $this->createQueryBuilder('n')
        ->where('n.user = :user')
        ->andWhere('n.shown > :yesterday OR n.shown IS NULL')
        ->setParameter('user', $user->getId())
        ->setParameter('yesterday', new \DateTime('-30 hours'), \Doctrine\DBAL\Types\Type::DATETIME)
        ->orderBy('n.shown', "ASC")
        ->getQuery();
      $this->notification['data'] = $qb->execute();
      $count = 0;
      /** @var Notification $note */
      foreach($this->notification['data'] as $note)
        if(!$note->isShown()) $count++;
      $this->notification['count'] = $count;
    }
    return $this->notification;
  }

  /** Sets all user's unread notifications to 'read'
   * @param User $user
   */
  public function updateNotificationCount($user) {
    /** @var Notification[] $notifications */
    $notifications = $this->findBy(array('user' => $user->getId(), 'shown' => NULL));
    foreach($notifications as $note) {
      $note->setShown();
    }
    $this->manager->flush();
  }

}
