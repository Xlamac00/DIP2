<?php
namespace App\AjaxController;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\Issue;
use App\Entity\IssueRole;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use App\Repository\IssueRepository;
use App\Repository\IssueRoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
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
   * @param Request $request
   * @return JsonResponse
   */
  public function entityInviteUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $username = $request->request->get('username');
      $role = $request->request->get('role');
      $entityId = $request->request->get('entity');

      $arrData = ['name' => $username, 'role' => $role, 'entity' => $entityId];
      return new JsonResponse($arrData);
    } else return null;
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
   * @Route("/tests", name="tests")
   */
  public function tests() {
      $user ='5acb2945384d19.23811990';
      $entityId = 'c4f8844e';
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
      die(var_dump($arrData));
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