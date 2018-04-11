<?php


namespace App\Repository;
use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\User;
use App\Entity\UserShare;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserShareRepository extends ServiceEntityRepository {
  private $manager;

  public function __construct(RegistryInterface $registry) {
    parent::__construct($registry, UserShare::class);
    $this->manager = $registry->getManager();
  }

  /** Checks if entity with shareLink exists and if so, gives user its set rights
   * to view this entity. Used by sharing entity with email with concrete users.
   *
   * @param string $shareLink - 32 char long share link
   * @param string $pageId - 8 char long entity page id
   * @param UserInterface $user - current user viewing the page
   *
   * @return string $shareRights
   */
  public function checkShareLinkRights($shareLink, $pageId, $user) {
    /** @var UserShare $userShare */
    $userShare = $this->findOneBy(["entity" => $pageId, "shareLink" => $shareLink]);
    if($userShare === null) // has not found anything
      throw new AuthenticationException('Invalid user link');
    if(!($user instanceof User))
      throw new AuthenticationException('Invalid user');
    if($user->getAnonymousEmail() === null) { // If the user is anonymous, pair his account with email which was shared
      $user->setAnonymousEmail($userShare->getEmail());
      $this->manager->persist($user);
      $this->manager->flush();
    }

    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->manager->getRepository(Issue::class);
    $issue = $issueRepository->getIssueByLink($pageId, $user);
    if($issue !== null) { // Its Issue - give user rights to view this issue
      /** @var IssueRoleRepository $issueRoleRepository */
      $issueRoleRepository = $this->manager->getRepository(IssueRole::class);
      $issueRoleRepository->giveUserRightsToIssue($user, $issue, $userShare->getRights(), null, null);
    }
    else {
      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->manager->getRepository(Board::class);
      $board = $boardRepository->getBoardByLink($pageId, $user);
      if($board !== null) {
        /** @var BoardRoleRepository $boardRoleRepository */
        $boardRoleRepository = $this->manager->getRepository(BoardRole::class);
        $boardRoleRepository->giveUserRightsToBoard($user, $board, $userShare->getRights(), null);
      }
      else throw new AuthenticationException('Entity not found');
    }
    return $userShare->getRights();
  }
}
