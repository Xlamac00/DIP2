<?php

namespace App\Repository;

use App\Entity\AbstractSharableEntity;
use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\BoardShareHistory;
use App\Entity\GaugeRole;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\IssueShareHistory;
use App\Entity\User;
use App\Exception\NotSufficientRightsException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class IssueRoleRepository extends ServiceEntityRepository {
  private $manager;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, IssueRole::class);
    $this->manager = $registry->getManager();
  }

  /** Returns all users with access to this Issue.
   * Once deleted users are not returned!
   *
   * @param string $issue - issue id (db)
   * @return IssueRole[] $users - list of all users in IssueRole
   */
  public function getIssueUsers($issue) {
    return $this->findBy(['issue' => $issue, 'isDeleted' => 0]);
  }

  /** Returns all Issue users that were invited or gained access via link and are not anonymous
   * @param Issue $issue
   * @return IssueRole[]
   * */
  public function getIssueUsersWithoutAnonymousLinks($issue) {
    $role = $this->getIssueUsers($issue->getId());
    $admins = array();
    $normal = array();
    $link = array();
    foreach($role as $item) {
      if(($item->isBoardHistory() || $item->isIssueHistory()) && $item->getUser()->isAnonymous())
        ;
      elseif($item->getRights() === Issue::ROLE_ADMIN)
        $admins[] = $item;
      elseif($item->isBoardHistory() || $item->isIssueHistory())
        $link[] = $item;
      else
        $normal[] = $item;
    }
    return array_merge($admins, $normal, $link);
  }

  /** Returns the rights the User have on the Issue.
   * @param User $user -
   * @param Issue $issue
   * @return IssueRole rights - users rights const
   */
  public function getUserRights($user, $issue) {
    /** @var IssueRole $role */
    $role = $this->findOneBy(["user" => $user->getId(), "issue" => $issue->getId(), 'isDeleted' => 0]);
    if(sizeof($role) <= 0) // user has no rights for this issue
      return null;
    return $role;
  }

  /** @return IssueRole */
  public function getUsersRights($pageId, $userId, $googleId = null) {
    if($userId instanceof User) {
      $user = $userId;
    }
    else {
      /** @var UserRepository $userRepository */
      $userRepository = $this->manager->getRepository(User::class);
      if($googleId !== null && strlen($googleId) > 10)
        $user = $userRepository->loadUserByGoogleId($googleId);
      else
        $user = $userRepository->loadUserByUsername($userId);
    }
    if($user == null) throw new AuthenticationException('No user found');

    if($pageId instanceof Issue) {
      $issue = $pageId;
    }
    else {
      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->manager->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($pageId, $user);
      if($issue == null) throw new AuthenticationException('No issue found');
    }
    /** @var BoardRoleRepository $boardRoleRepository */
    $boardRoleRepository = $this->manager->getRepository(BoardRole::class);
    $boardRights = $boardRoleRepository->getUserRights($user, $issue->getBoard());
    if($boardRights == null || !$boardRights->isShareEnabled() || !$boardRights->isActive() || $boardRights->isDeleted())
      throw new AuthenticationException('No rights found');

    $rights = $this->getUserRights($user, $issue);

    if($rights == null || !$rights->isShareEnabled() || !$rights->isActive() || $rights->isDeleted())
      throw new AuthenticationException('No rights found');

    // if Board is archived, return only rights to see it
    if($boardRights->getBoard()->isArchived()) {
      $rights->setRole(Issue::ROLE_READ);
      return $rights;
    }

    // If user has only right to read, check if he can at least change one gauge
    if($rights->getRights() === Issue::ROLE_READ) {
      foreach($issue->getGauges() as $gauge) {
        if($gauge->hasBindUser() && $gauge->getBindUserName() == $user->getUsername()) {
          $rights->setRole(Issue::ROLE_GAUGE);
          return $rights;
        }
      }
    }

    // if user(/issue) has rights to write, but user is only anonymous, give him rights to read only
    // (should have anonwrite rights!
    if($rights->getRights() === Issue::ROLE_WRITE && $user->isAnonymous())
      $rights->setRole(Issue::ROLE_READ);

    return $rights;
  }

  public function checkUsersRights($pageId, $userId, $googleId = null) {
     return $this->getUsersRights($pageId, $userId, $googleId)->getRights();
  }

  /** Changes individuals user rights to this Issue.
   * If new rights are different from the default ones and the rights were gained via a link,
   * it ignores any other changes to the link rights until this rights are not set to the default value.
   *
   * @param UserInterface $admin - currently logged user
   * @param string $issueId - id of the issue (db id)
   * @param string $uniqueLink - id of the user to be changed
   * @param string $newRights - constant from Board
   * @return boolean $is_active - if the user is active
   * @throws AuthenticationException, NotSufficientRightsException, UnsupportedUserException
   */
  public function changeUserRights($admin, $issueId, $uniqueLink, $newRights) {
    $role = $this->checkUsersRights($issueId, $admin);
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
    elseif($newRights == Board::ROLE_GAUGE)
      $role = Board::ROLE_READ;
    elseif($newRights == Board::ROLE_READ)
      $role = Board::ROLE_READ;
    elseif($newRights == Board::ROLE_VOID)
      $role = Board::ROLE_VOID;
    else
      throw new AuthenticationException('Invalid new user rights');

    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    $issue = $issueRepository->getIssue($issueId, $user);
    if($issue === null) throw new AuthenticationException('Issue not found');

    $issueRole = $this->getUserRights($user, $issue);
    $history = $issueRole->getIssueHistory();
    $nRole = $role == Board::ROLE_ANON ? Board::ROLE_WRITE : $role;
    if($history !== null) {
      $oldRole = $history->getRights();
      //normalize the roles to avoid errors with anonwrite vs write
      if ($oldRole == Board::ROLE_ANON) $oldRole = Board::ROLE_WRITE;
      if ($oldRole == $newRights) {
        $history->setOldRole(NULL);
      } else {
        $history->setOldRole($role);
      }
      $issueRole->setShareEnabled($nRole !== Board::ROLE_VOID);
      $this->manager->persist($history);
    }

    $bHistory = $issueRole->getBoardHistory();
    if($bHistory !== null) {
      $oldRole = $bHistory->getRights();
      if ($oldRole == $newRights) {
        $issueRole->setOldBoardRole(NULL);
      } else {
        $issueRole->setOldBoardRole($role);
      }
    }

    // Update IssueRight: role = newRole
    if($role == Board::ROLE_VOID) {
      $issueRole->setActive(false);
    }
    else {
      $issueRole->setRole($role);
      $issueRole->setActive(true);
    }
    $this->manager->persist($issueRole);
    $this->manager->flush();
    return $issueRole->isActive();
  }

  /** Removes users rights to see this Issue
   *
   * @param UserInterface $admin - currently logged user
   * @param string $issue - id of the issue
   * @param string $uniquelink - users unique link which rights have to be changed
   *
   * @throws AuthenticationException - not enough rights to make this change
   */
  public function deleteUser($admin, $issue, $uniquelink) {
    $role = $this->checkUsersRights($issue, $admin);
    if($role != Board::ROLE_ADMIN)
      throw new AuthenticationException('Not enough rights to make this change');

    /** @var UserRepository $userRepository */
    $userRepository = $this->manager->getRepository(User::class);
    if(is_numeric($uniquelink)) // google id
      $user = $userRepository->loadUserByGoogleId($uniquelink);
    else
      $user = $userRepository->loadUserByUsername($uniquelink);
    if($user === null)
      throw new AuthenticationException('No user found to change his rights');

    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    $issue = $issueRepository->getIssue($issue, $user);
    if($issue === null)
      throw new AuthenticationException('No Issue found');
    $rights = $this->getUserRights($user, $issue);
    $rights->delete();
    $this->manager->persist($rights);
    $this->manager->flush();
  }

  /** Checks if the issue has set the share link and if the link is ok.
   * If so, inserts new record into IssueRole and for each issue in the board sets the rights as well.
   * @param string $shareLink - 32 chars long string
   * @param string $pageId - 8 chars long Issue id
   * @param $user - current user
   * @throws AuthenticationException
   * */
  public function checkShareLinkRights($shareLink, $pageId, $user) {
    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    $issue = $issueRepository->getIssueByLink($pageId, $user, false);
    if($issue == null) throw new AuthenticationException('Issue not found');
    if($issue->getShareLink() == $shareLink && $issue->isShareEnabled()) {
      $rights = $issue->getShareRights();

      // If share rights are for anonymous only and user is anonymous, make it read only
      if($user instanceof User && $user->isAnonymous() && $rights === Issue::ROLE_WRITE)
        $rights = Issue::ROLE_READ;

      // save record about the link use
      $history = new IssueShareHistory();
      $history->setRole($rights);
      $history->setUser($user);
      $history->setIssue($issue);
      $history->setBoardHistory(null);
      $this->manager->persist($history);
      $this->manager->flush();

      $this->giveUserRightsToIssue($user, $issue, $rights, null, $history);
    }
    else
      throw new AuthenticationException('Sharing is not enabled');
  }

  /** Gives User rights to $rights Issue and see all other Issue in Board.
   * @param User $user
   * @param Issue $issue
   * @param string $rights
   * @param BoardShareHistory $boardHistory
   * @param IssueShareHistory $issueHistory
   */
  public function giveUserRightsToIssue($user, $issue, $rights, $boardHistory, $issueHistory) {
    $check = $this->getUserRights($user, $issue);
    if($check === null) {
      // set the rights for this user and this issue
      $role = new IssueRole();
      $role->setIssue($issue);
      $role->setRole($rights);
      $role->setUser($user);
      $role->setIssueHistory($issueHistory);
      $role->setBoardHistory($boardHistory);
      $this->manager->persist($role);
    }

    // add rights to see whole Board
    /** @var BoardRoleRepository $boardRoleRepository */
    $boardRoleRepository = $this->manager->getRepository(BoardRole::class);
    $boardRights = $boardRoleRepository->getUserRights($user, $issue->getBoard());
    if($boardRights === null) { // if the User does not have rights already
      $role = new BoardRole();
      $role->setBoard($issue->getBoard());
      $role->setRole(Issue::ROLE_READ);
      $role->setUser($user);
      $role->setBoardHistory($boardHistory);
      $role->setIssueHistory($issueHistory);
      $this->manager->persist($role);
    }

    // for all other Issue in the same Board, add rights to read
    foreach($issue->getBoard()->getIssues() as $boardIssue) {
      if($boardIssue->getPageId() !== $issue->getPageId()) { // every other Issue
        $check = $this->getUserRights($user, $boardIssue);
        if($check !== null && !$check->isDeleted()) continue; // user has already rights to this Issue

        $role = new IssueRole();
        $role->setIssue($boardIssue);
        $role->setRole(Issue::ROLE_READ);
        $role->setUser($user);
        $role->setIssueHistory($issueHistory);
        $role->setBoardHistory($boardHistory);
        $this->manager->persist($role);
      }
    }
    $this->manager->flush();
  }

}