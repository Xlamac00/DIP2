<?php

namespace App\Repository;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\IssueShareHistory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class IssueRepository extends AbstractSharableEntityRepository {
  /** @var  Issue */
  private $issue;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, Issue::class);
  }

  // Returns Issue entity for the given ID
  public function getIssue($issue_id, $forceFind = false) {
    if(!isset($this->issue) || $forceFind === true)
      $this->issue = $this->find($issue_id);
    return $this->issue;
  }

  /** Returns Issue entity for the given link.
   * If the user is not set null, calculates additional info for the Issue. Without it, the
   * Issue cannot be rendered!
   *
   * @param string $link
   * @param User   $user
   * @return Issue|null
   */
  public function getIssueByLink($link, $user) {
    if(!isset($this->issue)) {
      $this->issue = $this->findOneBy(["linkId" => $link]);
//      if($user !== null)
        $this->loadIssue($user);
    }
    return $this->issue;
  }

  private function loadIssue($user) {

    /** @var IssueRoleRepository $issueRoleRepository */
    $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
    $rights = $issueRoleRepository->getUserRights($user, $this->issue);
    $this->issue->setThisUserRights($rights);
  }

  public function getNumberOfGauges($issue_id = null) {
    if($issue_id === null) $issue_id = $this->issue->getId();
    $qb = $this->createQueryBuilder('q')
      ->select('DISTINCT g.id')
      ->from('App\Entity\Gauge', 'g')
      ->andWhere('g.issue = :issue')
      ->setParameter('issue', $issue_id)
      ->getQuery();
    $result = $qb->execute();
    return sizeof($result);
  }

  public function updateName($newName) {
    $this->issue->setName($newName);
    $this->manager->persist($this->issue);
    $this->manager->flush();
  }

  /** Creates new Issue.
   * Creates new issue, generates all its links (8char id, sharing link, ..). Makes $currentUser its admin
   * as well as all admins in the $board. Gives all users with access to the $board access to this Issue as well.
   *
   * @param string $name - name of the Issue
   * @param string $board_id - db id of the Board this Issue belongs to
   * @param UserInterface $currentUser - creator of the Issue, will be one of admins
   *
   * @return string link - link to the Issue
   */
  public function createNewIssue($name, $board_id, $currentUser) {
    /** @var Board $board */
    $board = $this->registry->getRepository(Board::class)->getBoard($board_id);

    $issue = new Issue();
    $issue->setName($name);
    $issue->setBoard($board);
    if($board->isShareEnabled()) { // if Board share is enabled
      $issue->setShareRights($board->getShareRights());
      $issue->setShareEnabled(true);
    }
    else { // else disable Issue sharing
      $issue->setShareRights(Board::ROLE_READ);
      $issue->setShareEnabled(false);
    }
    while(1) { // try generating random strings
      try{
        $issue->generateLinks();
        $this->manager->persist($issue);
        $this->manager->flush();
        break;
      }
      catch (UniqueConstraintViolationException $e) { //random string was not unique! (probably never gonna happen)
        continue;
      }
    }

    /** @var BoardRoleRepository $boardRole */
    $boardRole = $this->registry->getRepository(BoardRole::class);
    /** @var BoardRole $users */
    $users = $boardRole->getBoardUsers($board_id);
    // Add all Board users as this Issue users as well
    /** @var BoardRole $user */
    foreach ($users as $user) {
      if($user->getUser() !== $currentUser) { // its different then current user
        $role = new IssueRole();
        if($user->getRights() === Board::ROLE_ADMIN) // if he was admin in board, give him admin rights
          $role->setRole(Board::ROLE_ADMIN);
        elseif($board->isShareEnabled()) // else if the Board is sharable, give him its share rights
          $role->setRole($board->getShareRights());
        else  // else give him only rights to read
          $role->setRole(Board::ROLE_READ);
        $role->setIssue($issue);
        $role->setUser($user->getUser());
        $role->setBoardHistory($user->getBoardHistory());
        $role->setActive($user->isActive());
        $role->setShareEnabled($user->isShareEnabled());
        $this->manager->persist($role);
      }
    }
    $this->manager->flush();

    // set current user as admin - he created this issue
    $admin = new IssueRole();
    $admin->setRole(Board::ROLE_ADMIN);
    $admin->setIssue($issue);
    $admin->setUser($currentUser);
    $this->manager->persist($admin);
    $this->manager->flush();

    return $issue->getUrl();
  }

  /** Returns all users cooperating on this issue.
   * If the user has rights to read, but did no change there, he is not returned
   *
   * @param string $issueId - id of the issue
   * @param integer $onlyFirstX - if it should return all the users, or only first x
   *
   * @return array
   */
  public function getAllActiveUsers($issueId, $onlyFirstX = 0) {
    $result = array();
    // get all other guys who commented, in the order of comment count
    $qb = $this->createQueryBuilder('i')
      ->select('c, COUNT(c) as pocet')
      ->join('App\Entity\Gauge', 'g')
      ->andWhere('g.issue = i.id')
      ->join('App\Entity\GaugeChanges', 'c')
      ->andWhere('c.gauge = g.id')
      ->andWhere('i.id= :issue')
      ->andWhere('c.discard = 0')
      ->setParameter('issue', $issueId)
      ->groupBy('c.user')
      ->orderBy('pocet', 'DESC')
      ->getQuery();
    $changes = $qb->execute();
    foreach($changes as $change) {
      array_push($result, $change[0]->getUser());
    }

    if(empty($result)) { // If there are no changes by users, add at least all admins
      $qb = $this->createQueryBuilder('i')
        ->select('r')
        ->join('App\Entity\IssueRole', 'r')
        ->andWhere('r.role = :role')
        ->andWhere('r.issue = i.id')
        ->andWhere('i.id= :issue')
        ->setParameter('role', Board::ROLE_ADMIN)
        ->setParameter('issue', $issueId)
        ->getQuery();
      $users = $qb->execute();
      foreach($users as $user) { // get all admins to start
        array_unshift($result, $user->getUser());
      }
    }

    if($onlyFirstX === 0 || sizeof($result) <= $onlyFirstX+1) // return all users
      return $result;
    // get only first two users and return number of hidden users as array
    $cut = array_slice($result, 0, $onlyFirstX);
    $cut[$onlyFirstX+1] = ['count' => (sizeof($result)-$onlyFirstX)];
    return $cut;
  }

  /**Changes the value of gauge in the issue.
   *
   * @param $gaugeIndex - index of the gauge in the issue
   *   (gauges have to be ordered to find its real id!)
   * @param $value - new value of the issue
   * @param $userId - id of the currently logged user
   *
   * @return array - new gauge value from db or "error" text
   */
  public function gaugeValueChange($gaugeIndex, $value, $userId) {
    foreach($this->issue->getGauges() as $key => $data) { // all gauges in the issue
      if($key == $gaugeIndex) { // correct gauge (ordered)
        $gauge = new GaugeRepository($this->registry);
        $gauge->getGauge($data->getId());
        $oldValue = $gauge->getPreviousValue();
        $newValue = $gauge->gaugeValueChange($value);
        $gauge->gaugeValueLog($value, $userId);
        return
          ['color' => $data->getColor(),
           'oldValue' => round($oldValue),
           'newValue' => round($newValue)];
      }
    }
    return ["error"];
  }

  /** Recalculates position of all gauges in the issue.
   * If gauge_id and index are given, sets gauge to the index and updates the rest accordingly.
   * @param null $gauge_id - id of the gauge to change position specifically
   * @param null $index - index with new position for the guage (starts from 0)
   */
  public function updateGaugesIndex($gauge_id = null, $index = null) {
    $i = 0;
    $manager = $this->getEntityManager();
    // gauges are ordered by position by default
    foreach($this->issue->getGauges() as $key => $data) { // all gauges in the issue
      if($gauge_id != null) { // change position of concrete gauge
        if ($i == $index) // skip $i which was set specifically
          $i++;
        if ($data->getId() == $gauge_id) { // change this gauge
          $position = $index;
          $i--; // reuse the $i
        } else
          $position = $i;
      }else $position = $i;
      $data->setPosition($position);
      $manager->persist($data);
      $i++;
    }
    $manager->flush();
  }

  /**
   * @param User $user - currently logged user
   * @param $newRight - constant from Board entity with new rights for this Issue
   *
   * @return string|boolean $newRight - currently set rights for the Issue, or false if user has no rights
   */
  public function changeIssueShareRights($user, $newRight) {
    if($this->checkAdminRights($user, IssueRole::class, $this->issue) !== true) return false; // rights to manage

    // If Issue old_share_rights == newRight, set oldRight to null (was reset to default)
    if($this->issue->getOldShareRights() == $newRight)
      $this->issue->setOldShareRights(null);
    elseif($this->issue->getOldShareRights() == null) // oldRights was not set, change it
      $this->issue->setOldShareRights($this->issue->getShareRights());

    // Update Issue: share_rights = newRight
    $this->issue->setShareRights($newRight);
    $this->manager->persist($this->issue);
    $this->manager->flush();

    // Update IssueRole: only if the access was gained via share link and it was not overwritten by individual rights
    /** @var IssueRoleRepository $issueRoleRepository */
    $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
    $rights = $issueRoleRepository->getIssueUsers($this->issue->getId());
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
    $this->manager->flush();

    return $newRight;
  }

  /**
   * @param User $user - currently logged user
   * @param boolean $isAllowed - true to allow sharing via link, false to disable it
   *
   * @return boolean - the current state of Issue sharing
   */
  public function changeIssueShareEnabled($user, $isAllowed) {
    if($this->checkAdminRights($user, IssueRole::class, $this->issue) !== true) return false; // rights to manage

    // Update Issue: share_enabled = isAllowed
    $this->issue->setShareEnabled($isAllowed);
    $this->manager->persist($this->issue);
    $this->manager->flush();

    // Update IssueRole: only if the access was gained via share link and it was not overwritten by individual rights
    /** @var IssueRoleRepository $issueRoleRepository */
    $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
    $rights = $issueRoleRepository->getIssueUsers($this->issue->getId());
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
    $this->manager->flush();

    return $isAllowed;
  }
}
