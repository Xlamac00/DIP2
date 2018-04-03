<?php

namespace App\Repository;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\BoardShareHistory;
use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class BoardRepository extends AbstractSharableEntityRepository {
  /** @var  Board */
  private $board;

  public function __construct(RegistryInterface $registry) {
      parent::__construct($registry, Board::class);
  }

  /**
   * Returns the board entity.
   * If the User is given, it calculated additional info for the board (to be shown), if there is no user,
   * it only allows basic operations with this repository (eg. changing basic attributes in db).
   *
   * @param string    $boardId - id of the board
   * @param User|null $user - if there is no User, the Board cannot be rendered! (additional info wont be calculated)
   *
   * @return null|Board
   */
  public function getBoard($boardId, $user = null) {
    if(!isset($this->board)) {
      $this->board = $this->find($boardId);
      if($user !== null) //
        $this->loadBoard($user);
    }
    return $this->board;
  }

  public function getBoardByLink($boardLink, $user) {
    if(!isset($this->board)) {
      $this->board = $this->findOneBy(["linkId" => $boardLink]);
      $this->loadBoard($user);
    }
    return $this->board;
  }

  private function loadBoard($user) {
    /** @var BoardRoleRepository $boardRoleRepository */
    $boardRoleRepository = $this->manager->getRepository(BoardRole::class);
//    $this->board->setAllUsers($boardRoleRepository->getBoardUsers($this->board->getId()));
    $right = $boardRoleRepository->getUserRights($user, $this->board);
    $this->board->setThisUserRights($right);
    /** @var IssueRoleRepository $issueRoleRepository */
    $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    // count the number of changes and get the latest ones for each issue
    foreach($this->board->getIssues() as $issue) {
      /** @var GaugeChangesRepository $gaugeChangesRepository */
      $gaugeChangesRepository = $this->manager->getRepository(GaugeChanges::class);
      $changes = $gaugeChangesRepository->getAllChangesForIssue($issue->getId());
      $issue->setCountGaugeComments(sizeof($changes));
      $issue->setLatestGaugeComments(array_slice($changes, 0, 3));
      // gets users rights to this issue
      $rights = $issueRoleRepository->getUserRights($user, $issue);
      $issue->setThisUserRights($rights);
      $activeUsers = $issueRepository->getAllActiveUsers($issue->getId(), true);
      $issue->setActiveUsers($activeUsers);
      $users = $issueRoleRepository->getIssueUsers($issue->getId());
      $issue->setAllUsers($users);
    }
  }

  /**
   * @param User $user - currently logged user
   * @param $newRight - constant from Board entity with new rights for all issue in this Board
   *
   * @return string|boolean $newRight - currently set rights for the Board, or false if user has no rights
   */
  public function changeBoardShareRights($user, $newRight) {
    if($this->checkAdminRights($user, BoardRole::class, $this->board) !== true) return false; // rights to manage

    // Update Board: share_rights = newRight
    $this->board->setShareRights($newRight);
    $this->manager->persist($this->board);
    $this->manager->flush();

    // get all users that gained rights via this link and give them new rights for $entity
    // Get all users with rights to this Board
    /** @var BoardRoleRepository $boardRoleRepository */
    $boardRoleRepository = $this->manager->getRepository(BoardRole::class);
    $rights = $boardRoleRepository->getBoardUsers($this->board->getId());
    /** @var BoardRole $right */
    foreach($rights as $right) {
      if($right->getRights() !== Board::ROLE_ADMIN) {
        // User gained access via Board share link and it was not individually changed
        if($right->isBoardHistory() && $right->getBoardHistory()->getOldRole() == null) {
          $right->setRole($newRight);
          $this->manager->persist($right);
        }
      }
    }

    // Foreach Issue in this Board
    foreach($this->board->getIssues() as $issue) {
      // If the Issue has old_share_rights set to null => its own share rights were not changed and
      // it can be overwritten by this parent Board. Otherwise its ignored.
      if($issue->getOldShareRights() == null) {
        // Update Issue share rights
        $issue->setShareRights($newRight);

        // Foreach user with access to this Issue, that gained access via Issue share link or Board share link
        /** @var IssueRoleRepository $issueRoleRepository */
        $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
        $rights = $issueRoleRepository->getIssueUsers($issue->getId());
        /** @var IssueRole $right */
        foreach($rights as $right) {
          if($right->getRights() !== Board::ROLE_ADMIN) {
            // Access was gained via Board share link and it has old history null (was not changed individually)
            if($right->isBoardHistory() && $right->getBoardHistory()->getOldRole() == null) {
              $right->setRole($newRight);
              $this->manager->persist($right);
            }
            // Access was gained via Issue share link
            else if($right->isIssueHistory() && $right->getIssueHistory()->getOldRole() == null) {
              $right->setRole($newRight);
              $this->manager->persist($right);
            }
          }
        }
      }
    }
    $this->manager->flush();

//    $qb = $this->createQueryBuilder('b')
//      ->select('h.id')
//      ->join('App\Entity\BoardShareHistory', 'h')
//      ->andWhere('h.entity = b.id')
//      ->andWhere('h.oldRole IS NULL')
//      ->andWhere('b.id = :id')
//      ->setParameter('id', $this->board->getId())
//      ->getQuery();
//    $history = $qb->execute();
//    $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
//    foreach($history as $item) {
//      $roles = $issueRoleRepository->findBy(["boardHistory" => $item['id']]);
//      /** @var IssueRole $role */
//      foreach($roles as $role) {
//        if($role->getRights() !== Board::ROLE_ADMIN) {
//          $role->setRole($newRight);
//          $this->manager->persist($role);
//        }
//      }
//    }
    $this->manager->flush();
    return $newRight;
  }

  /** Changes if the Board share link is active or not.
   * If the link is active (isAllowed is true), any user with its link will automatically gain
   * sharing rights associated with this board for all its issue.
   *
   * @param User $user - currently logged user
   * @param boolean $isAllowed - true to allow sharing via link, false to disable it
   *
   * @return boolean - the current state of Board sharing
   */
  public function changeBoardShareEnabled($user, $isAllowed) {
    if($this->checkAdminRights($user, BoardRole::class, $this->board) !== true) return false; // rights to manage

    // Update Board: share_enabled = boolean
    $this->board->setShareEnabled($isAllowed);
    $this->manager->persist($this->board);

    // Get all users with rights to this Board
    /** @var BoardRoleRepository $boardRoleRepository */
    $boardRoleRepository = $this->manager->getRepository(BoardRole::class);
    $rights = $boardRoleRepository->getBoardUsers($this->board->getId());
    /** @var BoardRole $right */
    foreach($rights as $right) {
      if($right->getRights() !== Board::ROLE_ADMIN) {
        // User gained access via Board share link and it was not individually changed
        if($right->isBoardHistory() && $right->getBoardHistory()->getOldRole() == null) {
          $right->setShareEnabled($isAllowed);
          $this->manager->persist($right);
        }
      }
    }
    $this->manager->flush();

    // Foreach Issue in this Board
    foreach($this->board->getIssues() as $issue) {
      // If the Issue has old_share_rights set to null => its own share rights were not changed and
      // it can be overwritten by this parent Board. Otherwise its ignored.
      if($issue->getOldShareRights() == null) {
        // Update Issue share_enabled
        $issue->setShareEnabled($isAllowed);

        // Foreach user with access to this Issue, that gained access via Issue share link or Board share link
        /** @var IssueRoleRepository $issueRoleRepository */
        $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
        $rights = $issueRoleRepository->getIssueUsers($issue->getId());
        /** @var IssueRole $right */
        foreach($rights as $right) {
          if($right->getRights() !== Board::ROLE_ADMIN) {
            // Access was gained via Board share link and it has old history null (was not changed individually)
            if($right->isBoardHistory() && $right->getBoardHistory()->getOldRole() == null) {
              $right->setShareEnabled($isAllowed);
              $this->manager->persist($right);
            }
            // Access was gained via Issue share link
            else if($right->isIssueHistory() && $right->getIssueHistory()->getOldRole() == null) {
              $right->setShareEnabled($isAllowed);
              $this->manager->persist($right);
            }
          }
        }
      }
    }
    $this->manager->flush();

//    $allowed = parent::changeShareEnabled($user, $isAllowed, BoardRole::class, BoardShareHistory::class,
//      $this->board);
//    if($allowed === $isAllowed) { // change was successful, change all issue in board as well
//       $qb = $this->createQueryBuilder('b')
//        ->select('h.id')
//        ->join('App\Entity\BoardShareHistory', 'h')
//        ->andWhere('h.entity = b.id')
//        ->andWhere('h.oldRole IS NULL')
//        ->andWhere('b.id = :id')
//        ->setParameter('id', $this->board->getId())
//        ->getQuery();
//      $history = $qb->execute();
//      $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
//      foreach($history as $item) {
//        $roles = $issueRoleRepository->findBy(["boardHistory" => $item['id']]);
//        foreach($roles as $role) {
//          $role->setShareEnabled($isAllowed);
//          $this->manager->persist($role);
//        }
//      }
//      $this->manager->flush();
//    }
    return $isAllowed;
  }
}
