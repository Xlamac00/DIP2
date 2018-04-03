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
   * @Route("/ajax/boardChangeShareRights", name="board_ajax_change_rights")
   */
  public function boardChangeRights(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $board = $request->request->get('board');
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
      $boardRepository->getBoard($board);
      $new = $boardRepository->changeBoardShareRights($this->getUser(), $role);

      $arrData = ['board' => $board, 'option' => $new, 'anonymous' => $anonymous, 'input' => $option];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueChangeShareRights", name="issue_ajax_change_rights")
   */
  public function issueChangeRights(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issue');
      $option = $request->request->get('option');
      $anonymous = $request->request->get('anonymous');

      if($option === Board::ROLE_WRITE && $anonymous === "true")
        $role = Board::ROLE_ANON;
      else if($option === Board::ROLE_WRITE)
        $role = Board::ROLE_WRITE;
      else
        $role = Board::ROLE_READ;

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue);
      $new = $issueRepository->changeIssueShareRights($this->getUser(), $role);

      $arrData = ['issue' => $issue, 'option' => $new, 'anonymous' => $anonymous, 'input' => $option];
      return new JsonResponse($arrData);
    }
  }

  /**
   * Toggle the availability of share link - to enable or disable it
   * @Route("/ajax/boardChangeShare", name="board_ajax_change_share")
   */
  public function boardChangeShare(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $board = $request->request->get('board');
      $enable = $request->request->get('enable');

      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $boardRepository->getBoard($board);
      $new = $boardRepository->changeBoardShareEnabled($this->getUser(), $enable === 'true');

      $arrData = ['board' => $board, 'enable' => $new];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueChangeShare", name="issue_ajax_change_share")
   */
  public function issueChangeShare(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issue');
      $enable = $request->request->get('enable');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue);
      $new = $issueRepository->changeIssueShareEnabled($this->getUser(), $enable === 'true');

      $arrData = ['issue' => $issue, 'enable' => $new];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/boardInviteUser", name="ajax_board_user_invite")
   */
  public function boardInviteUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $username = $request->request->get('username');
      $role = $request->request->get('role');
      $board = $request->request->get('board');

      $arrData = ['name' => $username, 'role' => $role, 'board' => $board];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueInviteUser", name="ajax_issue_user_invite")
   */
  public function issueInviteUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $username = $request->request->get('username');
      $role = $request->request->get('role');
      $issue = $request->request->get('issue');

      $arrData = ['name' => $username, 'role' => $role, 'issue' => $issue ];
      return new JsonResponse($arrData);
    }
  }


  /**
   * @Route("/tests", name="tests")
   */
  public function tests() {
    //    $user = "5ab2cfaf237527.88179802";
    $user = "5ab3860491add4.72831813";
    $board = '1';
    $issue = 29;
    $newRole = 'ROLE_ISSUE_READ';

    //    $board = $request->request->get('board');
    $option = 'ROLE_ISSUE_WRITE';
    $anonymous = "true";

    $enabled = true;

    $roleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
    try {
      $enabled = $roleRepository->changeUserRights($this->getUser(), $board, $user, $newRole);
      $success = true;
    }
    catch (Exception $e) {
      $success = false;
    }

    $arrData = ['name' => $user, 'board' => $board, 'enabled' => $enabled, 'success' => $success];
    die(var_dump($arrData));
  }

  /**
   * @Route("/ajax/boardChangeUser", name="ajax_board_user_change")
   */
  public function boardChangeUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $user = $request->request->get('user');
      $board = $request->request->get('board');
      $newRole = $request->request->get('role');
      $enabled = true;

      /** @var BoardRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      try {
        $enabled = $roleRepository->changeUserRights($this->getUser(), $board, $user, $newRole);
        $success = true;
      }
      catch (Exception $e) {
        $success = false;
      }

      $arrData = ['name' => $user, 'board' => $board, 'enabled' => $enabled, 'success' => $success];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueChangeUser", name="ajax_issue_user_change")
   */
  public function issueChangeUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $user = $request->request->get('user');
      $issue = $request->request->get('issue');
      $newRole = $request->request->get('role');
      $enabled = true;

      /** @var IssueRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
      try {
        $enabled = $roleRepository->changeUserRights($this->getUser(), $issue, $user, $newRole);
        $success = true;
      }
      catch (Exception $e) {
        $success = false;
      }

      $arrData = ['name' => $user, 'issue' => $issue, 'enabled' => $enabled, 'success' => $success];
      return new JsonResponse($arrData);
    }
  }


  /**
   * @Route("/ajax/boardRemoveUser", name="ajax_board_user_remove")
   */
  public function boardRemoveUser(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $user = $request->request->get('user');
      $board = $request->request->get('board');
      $render = '';

      $roleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      try {
        $roleRepository->deleteUser($this->getUser(), $board, $user);
        $users = $roleRepository->getBoardUsers($board);
        $render = $this->renderView('dashboard/share-userlist.html.twig', ["users" => $users]);
        $success = true;
      }
      catch (AuthenticationException $e) {
        $success = false;
      }

      $arrData = ['name' => $user, 'board' => $board, 'result' => $render, 'success' => $success];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/boardGetUserlist", name="ajax_board_userlist_get")
   */
  public function boardGetUserlist(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $board = $request->request->get('board');

      /** @var BoardRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(BoardRole::class);
      $users = $roleRepository->getBoardUsers($board);
      $render = $this->renderView('dashboard/share-userlist.html.twig', ["users" => $users]);

      $arrData = ['board' => $board, 'result' => $render];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueGetUserlist", name="ajax_issue_userlist_get")
   */
  public function issueGetUserlist(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issue');

      /** @var IssueRoleRepository $roleRepository */
      $roleRepository = $this->getDoctrine()->getRepository(IssueRole::class);
      $users = $roleRepository->getIssueUsers($issue);
      $render = $this->renderView('dashboard/share-userlist.html.twig', ["users" => $users]);

      $arrData = ['issue' => $issue, 'result' => $render];
      return new JsonResponse($arrData);
    }
  }
}