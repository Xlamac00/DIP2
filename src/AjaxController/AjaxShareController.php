<?php
namespace App\AjaxController;

use App\Entity\AbstractSharableEntity;
use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Entity\Notification;
use App\Entity\User;
use App\Entity\UserShare;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use App\Repository\IssueRepository;
use App\Repository\IssueRoleRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AjaxShareController extends Controller {

  /**
   * @Route("/ajax/entityChangeShareRights", name="entity_ajax_change_rights")
   * @param Request $request
   * @return JsonResponse
   */
  public function entityChangeRights(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $entityId = $request->request->get('entity');
      $option = $request->request->get('option');
      $anonymous = $request->request->get('anonymous');

      if($option === Board::ROLE_WRITE && $anonymous === "true")
        $role = Board::ROLE_ANON;
      else if($option === Board::ROLE_WRITE)
        $role = Board::ROLE_WRITE;
      else
        $role = Board::ROLE_READ;

      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $board = $boardRepository->getBoard($entityId);
      if($board !== null) { // its Board
        $new = $boardRepository->changeBoardShareRights($this->getUser(), $role);
      }
      else { // its Issue
        /** @var IssueRepository $issueRepository */
        $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
        $issueRepository->getIssueByLink($entityId, $this->getUser());
        $new = $issueRepository->changeIssueShareRights($this->getUser(), $role);
      }

      $arrData = ['entity' => $entityId, 'option' => $new, 'anonymous' => $anonymous, 'input' => $option];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * Toggle the availability of share link - to enable or disable it
   * @Route("/ajax/entityChangeShare", name="entity_ajax_change_share")
   * @param Request $request
   * @return JsonResponse
   */
  public function entityChangeShare(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $entityId = $request->request->get('entity');
      $enable = $request->request->get('enable');

      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $board = $boardRepository->getBoard($entityId);
      if($board !== null) { // its Board
        $new = $boardRepository->changeBoardShareEnabled($this->getUser(), $enable === 'true');
      }
      else {
        /** @var IssueRepository $issueRepository */
        $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
        $issueRepository->getIssueByLink($entityId, $this->getUser());
        $new = $issueRepository->changeIssueShareEnabled($this->getUser(), $enable === 'true');
      }

      $arrData = ['entity' => $entityId, 'enable' => $new];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/entityInviteUser", name="ajax_entity_user_invite")
   * @param Request       $request
   * @param \Swift_Mailer $mailer
   *
   * @return JsonResponse
   */
  public function entityInviteUser(Request $request, \Swift_Mailer $mailer) {
    if ($request->isXmlHttpRequest()) {
      $username = $request->request->get('username');
      $role = $request->request->get('role');
      $entityId = $request->request->get('entity');

      /** @var UserRepository $userRepository */
      $userRepository = $this->getDoctrine()->getRepository(User::class);
      $result = array();
      $user = null;
      $email = null;
      // regex match from entity-sharing.js
      // email
      if(preg_match('/^.{2,}@[a-z0-9\.\-]{2,}\.[a-z0-9]+$/i', $username, $result) === 1) {
        $email = $result[0];
        $user = $userRepository->findUserByEmail(trim($result[0]));
      }
      // my user from db
      elseif(preg_match('/^.+\(.{2,}@ \.\.\. \)$/i', $username, $result) === 1) {
        $name = explode('(', $result[0]);
        $mail = explode('@', $name[1]);
        $user = $userRepository->findUserByNameAndEmail(trim($name[0]), trim($mail[0]));
      }

      if($role == AbstractSharableEntity::ROLE_WRITE)
        $newRole = AbstractSharableEntity::ROLE_ANON;
      else
        $newRole = AbstractSharableEntity::ROLE_READ;

      // in entity-sharing Issue are initiated by 8 char long pageId, but Boards are initiated by integer db id
      // which wont be that long. Dunno why is it this way, but can use it to en advantage.
      if(strlen($entityId) === 8) { // Its Issue
        /** @var IssueRepository $issueRepository */
        $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
        $entity = $issueRepository->getIssueByLink($entityId, $this->getUser());
        if($entity !== null) {
          $rights = $entity->getThisUserRights();
          if($rights === null || $rights->getRights() === Issue::ROLE_VOID) $entity = null;
        }
      }
      elseif(is_numeric($entityId)) { // its Board
        /** @var BoardRepository $boardRepository */
        $boardRepository = $this->getDoctrine()->getRepository(Board::class);
        $entity = $boardRepository->getBoard($entityId, $this->getUser());
        if($entity !== null) {
          $rights = $entity->getThisUserRights();
          if($rights === null || $rights->getRights() === Board::ROLE_VOID) $entity = null;
        }
      }
      else $entity = null;

      if($user instanceof User && $entity !== null) { // User is already in my DB
        if(strlen($entityId) === 8) {
          $this->inviteUserByEmail($user->getEmail(), $entity->getUrl(), $entity, $this->getUser(), $mailer);
          /** @var IssueRoleRepository $issueRoleRepository */
          $issueRoleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
          $issueRoleRepository->giveUserRightsToIssue($user, $entity, $newRole, null, null);
        }
        elseif(is_numeric($entityId)) {
          $this->inviteUserByEmail($user->getEmail(), $entity->getUrl(), $entity, $this->getUser(), $mailer);
          /** @var BoardRoleRepository $boardRoleRepository */
          $boardRoleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
          $boardRoleRepository->giveUserRightsToBoard($user, $entity, $newRole, null);
        }
        $notification = new Notification();
        $notification->setDate();
        $notification->setCreator($this->getUser());
        $notification->setUser($user);
        $notification->setUrl($entity->getUrl());
        $notification->setText($this->getUser()->getUsername().' invited you <br>to <b>'.$entity->getName().'</b>');
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($notification);
        $entityManager->flush();
      }
      elseif(strlen($email) > 2 && $entity !== null) { // only users email was included
        $share = new UserShare();
        $share->setUser($this->getUser());
        $share->setRole($newRole);
        $share->setEmail($email);
        $share->setEntity($entity);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($share);
        $entityManager->flush();

        $this->inviteUserByEmail($email, $share->getUrl(), $entity, $this->getUser(), $mailer);
      }

      $arrData = ['name' => $username, 'role' => $role, 'entity' => $entityId, 'email' => $entity !== null];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @param string                 $email
   * @param string                 $shareLink
   * @param AbstractSharableEntity $entity
   * @param User                   $owner
   * @param \Swift_Mailer          $mailer
   */
  private function inviteUserByEmail(string $email, string $shareLink,
                                     AbstractSharableEntity $entity, User $owner, \Swift_Mailer $mailer) {
      $message = (new \Swift_Message('Project invitation'))
        ->setFrom('project-manager@heroku.com')
        ->setTo($email)
        ->setBody(
          '<h2>Hello,</h2> '.$owner->getUsername().' ( '.$owner->getEmail().' )'
          .' wants to share an Project with you.'
          .'<p><h3>'.$entity->getName().'</h3>'
          .'<a href="https://xlamac00-dip.herokuapp.com/'.$shareLink.'" target="_blank">'.$shareLink.'</a></p>', // Its not an error!
          'text/html'
        );
      $mailer->send($message);
  }

  /**
   * @Route("/ajax/entityChangeUser", name="ajax_entity_user_change")
   * @param Request $request
   * @return null|JsonResponse
   */
  public function entityChangeUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $user = $request->request->get('user');
      $entityId = $request->request->get('entity');
      $newRole = $request->request->get('role');
      $enabled = true;

      /** @var BoardRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      try {
        $enabled = $roleRepository->changeUserRights($this->getUser(), $entityId, $user, $newRole);
        $success = true;
      }
      catch (AuthenticationException $e) {
        /** @var IssueRoleRepository $roleRepository */
        $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
        try {
          $enabled = $roleRepository->changeUserRights($this->getUser(), $entityId, $user, $newRole);
          $success = true;
        }
        catch (AuthenticationException $e) {
          $success = false;
        }
      }

      $arrData = ['name' => $user, 'entity' => $entityId, 'enabled' => $enabled, 'success' => $success];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/entityRemoveUser", name="ajax_entity_user_remove")
   * @param Request $request
   * @return null|JsonResponse
   */
  public function entityRemoveUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $user = $request->request->get('user');
      $entityId = $request->request->get('entity');
      $render = '';

      /** @var BoardRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      try {
        $roleRepository->deleteUser($this->getUser(), $entityId, $user);
        $users = $roleRepository->getBoardUsers($entityId);
        $render = $this->renderView('board/share-userlist.html.twig', ["users" => $users]);
        $success = true;
      }
      catch (AuthenticationException $e) {
        /** @var IssueRoleRepository $roleRepository */
        $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
        try {
          $roleRepository->deleteUser($this->getUser(), $entityId, $user);
          /** @var IssueRepository $issueRepository */
          $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
          $id = $issueRepository->getIdByLink($entityId);
          /** @var IssueRoleRepository $roleRepository */
          $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
          $users = $roleRepository->getIssueUsers($id);
          $render = $this->renderView('board/share-userlist.html.twig', ["users" => $users]);
          $success = true;
        }
        catch (AuthenticationException $e) {
          $success = false;
        }
      }

      $arrData = ['name' => $user, 'entity' => $entityId, 'result' => $render, 'success' => $success];
      return new JsonResponse($arrData);
    } else return null;
  }

  /**
   * @Route("/ajax/entityGetUserlist", name="ajax_entity_userlist_get")
   * @param Request $request
   * @return null|JsonResponse
   */
  public function entityGetUserlist(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $entityId = $request->request->get('entity');

      /** @var BoardRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      $users = $roleRepository->getBoardUsers($entityId);
      if(sizeof($users) === 0) { // its not Board, try Issue
        /** @var IssueRepository $issueRepository */
        $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
        $id = $issueRepository->getIdByLink($entityId);
        /** @var IssueRoleRepository $roleRepository */
        $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
        $users = $roleRepository->getIssueUsers($id);
      }
      $render = $this->renderView('board/share-userlist.html.twig', ["users" => $users]);

      $arrData = ['entity' => $entityId, 'result' => $render];
      return new JsonResponse($arrData);
    } else return null;
  }
}