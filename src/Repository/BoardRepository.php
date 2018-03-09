<?php

namespace App\Repository;

use App\Entity\Board;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class BoardRepository extends ServiceEntityRepository {
  private $registry;
  private $board;

    public function __construct(RegistryInterface $registry) {
        parent::__construct($registry, Board::class);
        $this->registry = $registry;
    }

    public function getBoard() {
      if(!isset($this->board))
        $this->getBoardFromDb(1);
      return $this->board;
    }

    private function getBoardFromDb($board_id) {
      $qb = $this->createQueryBuilder('b')
        ->andWhere('b.id = :id')
        ->setParameter('id', $board_id)
        ->getQuery();
      $result = $qb->execute();
      $this->board = $result[0];

      $qa = $this->createQueryBuilder('q')
        ->select('i.id')
        ->from('App:Issue', 'i')
        ->andWhere('i.board = :id')
        ->andWhere('q.id = :id')
        ->setParameter('id', $board_id)
        ->getQuery();
      $issues = $qa->execute();

      foreach ($issues as $data) {
        $issueRepository = new IssueRepository($this->registry);
        $issue = $issueRepository->getIssue($data['id']);
        $this->board->setIssue($issue);
      }
    }
}
