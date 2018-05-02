<?php
namespace App\Security\User;

use App\Entity\BoardRole;
use App\Entity\IssueRole;
use App\Repository\BoardRoleRepository;
use App\Repository\IssueRoleRepository;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class OwnAuthUserProvider extends OAuthUserProvider {
  /** @var UserRepository $repository */
  private $repository;
  /** @var  BoardRoleRepository */
  private $boardRoleRepository;
  /** @var  IssueRoleRepository */
  private $issueRoleRepository;
  private $manager;

  public function __construct(UserRepository $userRepository, BoardRoleRepository $boardRoleRepository,
                              IssueRoleRepository $issueRoleRepository, ObjectManager $manager) {
    $this->repository = $userRepository;
    $this->boardRoleRepository = $boardRoleRepository;
    $this->issueRoleRepository = $issueRoleRepository;
    $this->manager = $manager;
  }

  /**
   * Loads the user by a given UserResponseInterface object.
   *
   * @param UserResponseInterface $response
   *
   * @return UserInterface
   * @throws AccountExpiredException if the google id cookie is empty
   */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response) {
      try {
        $user = $this->repository->loadUserByGoogleId($response->getUsername());
      }
      catch (UnsupportedUserException $e) { // create new user
        $request = Request::createFromGlobals();
        $clientId = $request->cookies->get('clientId');
        if(strlen($clientId) <= 0) // google id cookie is empty
          throw new AccountExpiredException('Empty clientId cookie');
        $user = $this->repository->createNewGoogleUser($clientId, $response->getUsername(),
          $response->getNickname(), $response->getEmail(), $response->getProfilePicture());
      }

      // check Google user's rights in comparison with his old anonymous rights
      // and give Google user all anonymous's rights as well
      $anonUser = $this->repository->loadUserByUsername($user->getAnonymousLink());
//      if(!$anonUser->isAnonymous()) die('not anonymous!!');
      $boards = $this->boardRoleRepository->getUserBoards($anonUser);
      /** @var BoardRole $board */
      foreach($boards as $board) {
        if($board->isActive() && !$board->isDeleted() && $board->isShareEnabled()) {
          $rights = $this->boardRoleRepository->getUserRights($user, $board->getBoard());// get rights for Google user
          if($rights === null) { // if Google user had no rights but anonymous user had them
            $right = new BoardRole();
            $right->setBoard($board->getBoard());
            $right->setRole($board->getRights());
            $right->setUser($user);
            $right->setBoardHistory(null);
            $this->manager->persist($right);
          }
          foreach ($board->getBoard()->getIssues() as $issue) {
            $issueRights = $this->issueRoleRepository->getUserRights($user, $issue); // Google users rights
            if($issueRights === null) {
              $anonRights = $this->issueRoleRepository->getUserRights($anonUser, $issue);
              if($anonRights != null && $anonRights->isActive()
                && !$anonRights->isDeleted() && $anonRights->isShareEnabled()) {
                $right = new IssueRole();
                $right->setUser($user);
                $right->setRole($anonRights->getRights());
                $right->setIssue($issue);
                $right->setBoardHistory(null);
                $right->setIssueHistory(null);
                $this->manager->persist($right);
              }
            }
          }
        }
      }
      $this->manager->flush();

      return $user;
    }
}