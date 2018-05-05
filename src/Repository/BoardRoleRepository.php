<?php

namespace App\Repository;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\BoardShareHistory;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\IssueShareHistory;
use App\Entity\User;
use App\Exception\NotSufficientRightsException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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
   * @return BoardRole[] $users - list of all users in BoardRole
   */
  public function getBoardUsers($board) {
    return $this->findBy(['board' => $board, 'isDeleted' => 0]);
  }

  /** Returns all boards this user has access to.
   * @param UserInterface $user - currently logged user
   * @return BoardRole[]
   */
  public function getUserBoards($user) {
    return $this->findBy(['user' => $user, 'isDeleted' => 0]);
  }

  /** Returns all boards the user has access to and separates favorite boards.
   * @param User $user
   * @return array - [roles, favorite]
   */
  public function getUserBoardsAndFavorite($user) {
    $roles = $this->getUserBoards($user);
    $boards = array();
    $favorite = array();
    // get users right to board
    foreach($roles as $role) {
      if($role->getRights() !== null && $role->getRights() !== Board::ROLE_VOID
        && $role->isActive() === true && !$role->isDeleted()) {
        if($role->getBoard()->isArchived())
          $archived[] = $role;
        else {
          $boards[] = $role;
          if ($role->isFavorite())
            $favorite[] = $role;
        }
      }
    }
    return ['boards' => $boards, 'favorite' => $favorite, 'archived' => $archived];
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
    if($admin instanceof User && $admin->getUniqueLink() == $uniquelink) {
      // User has right to delete himself
    }
    else { // if deleting someone else, check users rights to this board
      $role = $this->checkUsersRights($board, $admin);
      if ($role != Board::ROLE_ADMIN)
        throw new AuthenticationException('Not enough rights to make this change');
    }

    /** @var UserRepository $userRepository */
    $userRepository = $this->manager->getRepository(User::class);
    if(is_numeric($uniquelink)) // google id
      $user = $userRepository->loadUserByGoogleId($uniquelink);
    else
      $user = $userRepository->loadUserByUsername($uniquelink);
    if($user === null)
      throw new AuthenticationException('No user found to change his rights');

    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->manager->getRepository(Board::class);
    $board = $boardRepository->getBoard($board);
    if($board === null)
      throw new AuthenticationException('No Board found');
    $rights = $this->getUserRights($user, $board);
    $rights->delete();
    $this->manager->persist($rights);
    $this->manager->flush();

    foreach($board->getIssues() as $issue) {
      /** @var IssueRoleRepository $issueRoleRepository */
      $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
      $right = $issueRoleRepository->getUserRights($user, $issue);
      if($right !== null) {
        $right->delete();
        $this->manager->persist($right);
      }
    }
    $this->manager->flush();
  }

  /** Returns the users rights to use given board.
   *
   * @param string $boardId - id of the board the rights are for
   * @param string|User $userId - either User entity or 20+ char unique user link
   * @param string|null $googleId - either null for anonymous user or google id
   *
   * @return BoardRole - one of the constants set in the Board entity
   */
  public function getUsersRights($boardId, $userId, $googleId = null) {
    if($userId instanceof User) {
      $user = $userId;
    }
    else {
      /** @var UserRepository $userRepository */
      $userRepository = $this->manager->getRepository(User::class);
      if ($googleId === null)
        $user = $userRepository->loadUser($userId);
      else
        $user = $userRepository->loadUserByGoogleId($googleId);
    }

    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->manager->getRepository(Board::class);
    if($boardId instanceof Board)
      $board = $boardId;
    else if(strlen($boardId) == 8)
      $board = $boardRepository->getBoardByLink($boardId, $user);
    else
      $board = $boardRepository->getBoard($boardId, $user);

    if($board === null)
      throw new AuthenticationException('No board found');

    $rights = $this->getUserRights($user, $board);
    if($rights === null)
      throw new AuthenticationException('No rights for the user');
    if($rights->isDeleted() || !$rights->isActive())
      throw new AuthenticationException('User deleted');

    // Board is archived, return only read rights
    if($board->isArchived()) {
      $rights->setRole(Board::ROLE_READ);
    }

    return $rights;
  }

  public function checkUsersRights($boardId, $userId, $googleId = null) {
    return $this->getUsersRights($boardId, $userId, $googleId)->getRights();
  }

  /** Checks if the board has set the share link and if the link is ok.
   * If so, inserts new record into BoardRole and for each issue in the board sets the rights as well.
   * @param string $shareLink - 32 chars long string
   * @param string $pageId - 8 chars long board id
   * @param $user - current user
   * */
  public function checkShareLinkRights($shareLink, $pageId, $user) {
    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->manager->getRepository(Board::class);
    $board = $boardRepository->getBoardByShareLink($pageId, $shareLink);
    if($board === null) // has not found anything
      throw new AuthenticationException('This board has no share link enabled');
    $rights = $board->getShareRights();

    // save record about the link use
    $bHistory = new BoardShareHistory();
    $bHistory->setRole($rights);
    $bHistory->setUser($user);
    $bHistory->setBoard($board);
    $this->manager->persist($bHistory);
    $this->manager->flush();

    $this->giveUserRightsToBoard($user, $board, $rights, $bHistory);

    return $rights;
  }

  /** Gives user $rights to given Board and all its Issues
   * @param User $user
   * @param Board $board
   * @param string $rights
   * @param BoardShareHistory $boardHistory
   */
  public function giveUserRightsToBoard($user, $board, $rights, $boardHistory) {
    $check = $this->getUserRights($user, $board);
    if($check === null) {
      // set the rights for this user and this board
      $role = new BoardRole();
      $role->setBoard($board);
      $role->setRole($rights);
      $role->setUser($user);
      $role->setBoardHistory($boardHistory);
      $this->manager->persist($role);
    }

    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    /** @var IssueRoleRepository $issueRoleRepository */
    $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
    // set same rights for each Issue in this Board
    $issues = $issueRepository->getIssuesInBoard($board->getId());
    foreach($issues as $issue) {
      $check = $issueRoleRepository->getUserRights($user, $issue);
      if($check !== null && !$check->isDeleted()) continue; // skip issues that already have rights for this user

      $role = new IssueRole();
      $role->setUser($user);
      $role->setIssue($issue);
      $role->setRole($rights);
      $role->setIssueHistory(null);
      $role->setBoardHistory($boardHistory);
      $this->manager->persist($role);

      // insert share history for issue as well
      $history = new IssueShareHistory();
      $history->setRole($rights);
      $history->setUser($user);
      $history->setIssue($issue);
      $history->setBoardHistory($boardHistory);
      $this->manager->persist($history);
    }
    $this->manager->flush();
  }
}