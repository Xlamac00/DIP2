<?php

namespace App\Repository;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\BoardShareHistory;
use App\Entity\IssueRole;
use App\Entity\IssueShareHistory;
use App\Entity\User;
use App\Exception\NotSufficientRightsException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class BoardRoleRepository extends ServiceEntityRepository {
  private $manager;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, BoardRole::class);
    $this->manager = $this->getEntityManager();
  }

  /** Returns all users with access to this Board.
   * Once deleted users are not returned!
   *
   * @param string $board - board id (db)
   * @return array $users - list of all users in BoardRole
   */
  public function getBoardUsers($board) {
    return $this->findBy(['board' => $board, 'isDeleted' => 0]);
  }

  /** Returns all boards this user has access to.
   *
   * @param UserInterface $user - currently logged user
   * @return array
   */
  public function getUserBoards($user) {
    return $this->findBy(['user' => $user, 'isDeleted' => 0]);
  }

  /** Returns the rights the User have on this Board.
   * @param User $user -
   * @param Board $board
   * @return BoardRole rights - users rights const
   */
  public function getUserRights($user, $board) {
    /** @var BoardRole $role */
    $role = $this->findOneBy(["user" => $user->getId(), "board" => $board->getId(), 'isDeleted' => 0]);
    if(sizeof($role) <= 0) // user has no rights for this issue
      return null;
    return $role;
  }

  /** Changes users rights to see this Board and all its Issues
   *
   * @param UserInterface $admin - currently logged user
   * @param string $boardId - id of the board (db id)
   * @param string $uniqueLink - id of the user to be changed
   * @param string $newRights - constant from Board
   * @return boolean $is_active - if the user is active
   * @throws AuthenticationException, NotSufficientRightsException, UnsupportedUserException
   */
  public function changeUserRights($admin, $boardId, $uniqueLink, $newRights) {
    $role = $this->checkUsersRights($boardId, $admin);
    if($role != Board::ROLE_ADMIN) { throw new NotSufficientRightsException("Not sufficient rights"); }

    /** @var UserRepository $userRepository */
    $userRepository = $this->manager->getRepository(User::class);
    $user = $userRepository->loadUser($uniqueLink);

    // Get role for the user (logged/anonymous, ...)
    if($newRights == Board::ROLE_WRITE) {
      if($user->isAnonymous())
        $role = Board::ROLE_ANON;
      else
        $role = Board::ROLE_WRITE;
    }
    elseif($newRights == Board::ROLE_READ)
      $role = Board::ROLE_READ;
    elseif($newRights == Board::ROLE_VOID)
      $role = Board::ROLE_VOID;
    else
      throw new AuthenticationException('Invalid new user rights');

    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->manager->getRepository(Board::class);
    $board = $boardRepository->getBoard($boardId);
    if($board === null) throw new AuthenticationException('Board not found');

    // Check if the user gained access via a sharing link
    // If so and you change his rights, he wont be affected by link disable in the future
    // unless you change his rights back to first position
    $boardRole = $this->getUserRights($user, $board);
    $set_share_parent = false;
    $set_share_true = false;
    $history = $boardRole->getBoardHistory();
    if($history !== null) {
      $oldRole = $history->getOldRole();
      //normalize the roles to avoid errors with anonwrite vs write
      if ($oldRole == Board::ROLE_ANON) $oldRole = Board::ROLE_WRITE;
      $nRole = $role == Board::ROLE_ANON ? Board::ROLE_WRITE : $role;
      if ($oldRole == $newRights) {
        $history->setOldRole(NULL);
        $set_share_parent = true;
      } else {
        $history->setOldRole($role);
        if ($nRole !== Board::ROLE_VOID) {
          $set_share_true = true;
        }
      }
      $boardRole->setShareEnabled($nRole !== Board::ROLE_VOID);
      $this->manager->persist($history);
    }

    // Update BoardRight: role = newRole
    if($role == Board::ROLE_VOID) {
      $boardRole->setActive(false);
    }
    else {
      $boardRole->setRole($role);
      $boardRole->setActive(true);
    }
    $this->manager->persist($boardRole);
    $this->manager->flush();

    foreach($board->getIssues() as $issue) {
      /** @var IssueRoleRepository $issueRoleRepository */
      $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
      $issueRole = $issueRoleRepository->getUserRights($user, $issue);
      if($issueRole != null) {
        if($issueRole->getRights() != Board::ROLE_ADMIN) {
          if($issueRole->isIssueHistory() && $issueRole->getIssueHistory()->getOldRole() == null) {
            if($role == Board::ROLE_VOID)
              $issueRole->setActive(false);
            else {
              $issueRole->setActive(true);
              $issueRole->setRole($role);
            }
            if($set_share_true === true) // board sharing rights were individually changed, change it for all issue as well
              $issueRole->setShareEnabled(true);
            elseif($set_share_parent === true) // board sharing rights were set to default, set it back for all issues
              $issueRole->setShareEnabled($role !== Board::ROLE_VOID);
            $this->manager->persist($issueRole);
          }
        }
      }
    }
    $this->manager->flush();
    return $boardRole->isActive();
  }

  /** Removes users rights to see this Board and its Issues
   *
   * @param UserInterface $admin - currently logged user
   * @param string $board - id of the board
   * @param string $uniquelink - users unique link which rights have to be changed
   *
   * @throws AuthenticationException - not enough rights to make this change
   */
  public function deleteUser($admin, $board, $uniquelink) {
    $role = $this->checkUsersRights($board, $admin);
    if($role != Board::ROLE_ADMIN)
      throw new AuthenticationException('Not enough rights to make this change');

    if(is_numeric($uniquelink)) // google id
      $where = 'u.googleId = :uniquelink';
    else // my own user, they have dot and chars in the id
      $where = 'u.link = :uniquelink AND u.googleId IS NULL';

    $qb = $this->createQueryBuilder('r')
      ->join('App\Entity\User', 'u')
      ->andWhere('r.user = u.id')
      ->andWhere($where)
      ->andWhere('r.board = :boardId')
      ->setParameter('uniquelink', $uniquelink)
      ->setParameter('boardId', $board)
      ->getQuery();
    $user = $qb->execute();
    if(sizeof($user) != 1)
      throw new AuthenticationException('No user found to change his rights');
    $user[0]->delete();
    $this->manager->persist($user[0]);
    $this->manager->flush();

    // Get all the issues for the board and its right and remove user from them
    $query = $this->manager->createQuery(
      'SELECT r
        FROM App\Entity\IssueRole r
        JOIN App\Entity\Issue i
        JOIN App\Entity\User u
        WHERE i.board = :boardId
          AND r.issue = i.id
          AND r.user = u.id
          AND '.$where
    )->setParameter('boardId', $board)
      ->setParameter('uniquelink', $uniquelink);
    $issues = $query->execute();
    foreach($issues as $issue) {
      $issue->delete();
      $this->manager->persist($issue);
    }
    $this->manager->flush();
  }

  /** Returns the users rights to use given board.
   *
   * @param string $boardId - id of the board the rights are for
   * @param string|User $userId - either User entity or 20+ char unique user link
   * @param string|null $googleId - either null for anonymous user or google id
   *
   * @return $role - one of the constants set in the Board entity
   */
  public function checkUsersRights($boardId, $userId, $googleId = null) {
    if($userId instanceof User) {
      if($userId->isAnonymous())
        $googleId = null;
      else
        $googleId = $userId->getGoogleId();
      $userId = $userId->getUniqueLink();
    }
    $identifier = (strlen($boardId) == 8 ? 'linkId' : 'id'); // if its linkId or dbId
    $qb = $this->createQueryBuilder('r')
      ->select('DISTINCT r.role')
      ->join('App\Entity\User', 'u')
      ->andWhere('r.user = u.id')
      ->join('App\Entity\Board', 'b')
      ->andWhere('b.'.$identifier.' = :pageId')
      ->andWhere('r.board = b.id')
      ->setParameter('pageId', $boardId);
    // get user whether he is logged by google id or is anonymous
    if($googleId !== null && strlen($googleId) > 10)
      $q = $qb->andWhere('u.googleId = :userId')
        ->setParameter('userId', $googleId);
    else
      $q = $qb->andWhere('u.link = :userId')
        ->andWhere('u.googleId is null')
        ->setParameter('userId', $userId);

    $result = $q->getQuery()->execute();
    if(sizeof($result) <= 0)
      throw new AuthenticationException('No rights found');
    return $result[0]['role'];
  }

  /** Checks if the board has set the share link and if the link is ok.
   * If so, inserts new record into BoardRole and for each issue in the board sets the rights as well.
   * @param string $shareLink - 32 chars long string
   * @param string $pageId - 8 chars long board id
   * @param UserInterface $user - current user
   * */
  public function checkShareLinkRights($shareLink, $pageId, $user) {
    // check if BOARD has set the share link and its enabled
    $query = $this->manager->createQuery(
      'SELECT b
        FROM App\Entity\Board b
        WHERE b.linkId = :id
          AND b.shareLink = :link
          AND b.shareEnabled = 1'
    )->setParameter('id', $pageId)
    ->setParameter('link', $shareLink);
    $board = $query->execute();
    if(sizeof($board) != 1) // has not found anything
      throw new AuthenticationException('This board has no share link enabled');
    $rights = $board[0]->getShareRights();

    // save record about the link use
    $bHistory = new BoardShareHistory();
    $bHistory->setRole($rights);
    $bHistory->setUser($user);
    $bHistory->setBoard($board[0]);
    $this->manager->persist($bHistory);

    // set the rights for this user and this board
    $role = new BoardRole();
    $role->setBoard($board[0]);
    $role->setRole($rights);
    $role->setUser($user);
    $role->setBoardHistory($bHistory);
    $this->manager->persist($role);

    // set rights for each issue in this board
    $query = $this->manager->createQuery(
      'SELECT i FROM App\Entity\Issue i
       WHERE i.board = :id'
    )->setParameter('id', $board[0]->getId());
    $issues = $query->execute();
    $query1 = $this->manager->createQuery(
      'SELECT r FROM App\Entity\IssueRole r
       WHERE r.user = :user
         AND r.issue = :iid'
    )->setParameter('user', $user->getId());
    foreach($issues as $issue) {
      $query1->setParameter('iid', $issue->getId());
      $result = $query1->execute();
      if(sizeof($result) != 0) continue; // skip issues that already have rights for this user
      $role = new IssueRole();
      $role->setUser($user);
      $role->setIssue($issue);
      $role->setRole($rights);
      $role->setBoardHistory($bHistory);
      $this->manager->persist($role);

      // insert share history for issue as well
      $history = new IssueShareHistory();
      $history->setRole($rights);
      $history->setUser($user);
      $history->setIssue($issue);
      $history->setBoardHistory($bHistory);
      $this->manager->persist($history);
    }
    $this->manager->flush();
    return $rights;
  }

}