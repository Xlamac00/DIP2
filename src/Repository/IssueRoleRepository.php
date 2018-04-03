<?php

namespace App\Repository;

use App\Entity\Board;
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
   * @return array $users - list of all users in IssueRole
   */
  public function getIssueUsers($issue) {
    return $this->findBy(['issue' => $issue, 'isDeleted' => 0]);
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

  public function checkUsersRights($pageId, $userId, $googleId = null) {
    if($userId instanceof User) {
      $user = $userId;
//      if($userId->isAnonymous())
//        $googleId = null;
//      else
//        $googleId = $userId->getGoogleId();
//      $userId = $userId->getUniqueLink();
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

    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    $issue = $issueRepository->getIssueByLink($pageId, $user);

    $rights = $this->getUserRights($user, $issue);
    if($rights == null || !$rights->isShareEnabled()) throw new AuthenticationException('No rights found');
    return $rights->getRights();
//    $qb = $this->createQueryBuilder('r')
//      ->select('DISTINCT r.role')
//      ->join('App\Entity\User', 'u')
//      ->andWhere('r.user = u.id')
//      ->join('App\Entity\Issue', 'i')
//      ->andWhere('i.id = :pageId')
//      ->andWhere('r.issue = i.id')
//      ->setParameter('pageId', $pageId);
//    // get user whether he is logged by google id or is anonymous
//    if($googleId !== null && strlen($googleId) > 10)
//      $q = $qb->andWhere('u.googleId = :userId')
//              ->setParameter('userId', $googleId);
//    else
//      $q = $qb->andWhere('u.link = :userId')
//        ->andWhere('u.googleId is null')
//        ->setParameter('userId', $userId);
//
//    $result = $q->getQuery()->execute();
//    die(sizeof($result)."A");
//    if(sizeof($result) <= 0)
//      throw new AuthenticationException('No rights found');
//    return $result[0]['role'];
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
    elseif($newRights == Board::ROLE_READ)
      $role = Board::ROLE_READ;
    elseif($newRights == Board::ROLE_VOID)
      $role = Board::ROLE_VOID;
    else
      throw new AuthenticationException('Invalid new user rights');

    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    $issue = $issueRepository->getIssue($issueId);
    if($issue === null) throw new AuthenticationException('Issue not found');

    $issueRole = $this->getUserRights($user, $issue);
    $history = $issueRole->getIssueHistory();
    if($history !== null) {
      $oldRole = $history->getOldRole();
      //normalize the roles to avoid errors with anonwrite vs write
      if ($oldRole == Board::ROLE_ANON) $oldRole = Board::ROLE_WRITE;
      $nRole = $role == Board::ROLE_ANON ? Board::ROLE_WRITE : $role;
      if ($oldRole == $newRights) {
        $history->setOldRole(NULL);
      } else {
        $history->setOldRole($role);
      }
      $issueRole->setShareEnabled($nRole !== Board::ROLE_VOID);
      $this->manager->persist($history);
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
  }

  /** Checks if the issue has set the share link and if the link is ok.
   * If so, inserts new record into IssueRole and for each issue in the board sets the rights as well.
   * @param string $shareLink - 32 chars long string
   * @param string $pageId - 8 chars long board id
   * @param $user - current user
   * @throws AuthenticationException
   * */
  public function checkShareLinkRights($shareLink, $pageId, $user) {
    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    $issue = $issueRepository->getIssueByLink($pageId, $user);
    if($issue == null) throw new AuthenticationException('Issue not found');
    if($issue->getShareLink() == $shareLink && $issue->isShareEnabled()) {
      $rights = $issue->getShareRights();

      // save record about the link use
      $history = new IssueShareHistory();
      $history->setRole($rights);
      $history->setUser($user);
      $history->setIssue($issue);
      $history->setBoardHistory(null);
      $this->manager->persist($history);

      // set the rights for this user and this issue
      $role = new IssueRole();
      $role->setIssue($issue);
      $role->setRole($rights);
      $role->setUser($user);
      $role->setIssueHistory($history);
      $role->setBoardHistory(null);
      $this->manager->persist($role);
    }
    else
      throw new AuthenticationException('Sharing is not enabled');
  }

}