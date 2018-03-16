<?php

namespace App\Repository;

use App\Entity\Board;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BoardRepository extends ServiceEntityRepository {
  private $registry;
  private $manager;
  private $board;

    public function __construct(RegistryInterface $registry) {
        parent::__construct($registry, Board::class);
        $this->registry = $registry;
        $this->manager = $registry->getManager();
    }

    public function getBoard($board_id) {
      if(!isset($this->board))
        $this->board = $this->find($board_id);
      return $this->board;
    }
}
