<?php

namespace App\Repository;

use App\Entity\BoardShareHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BoardShareHistoryRepository extends ServiceEntityRepository {
  private $manager;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, BoardShareHistory::class);
    $this->manager = $this->getEntityManager();
  }
}