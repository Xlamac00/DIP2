<?php

namespace App\AjaxController;

use App\Entity\Board;
use App\Entity\Bug;
use App\Entity\Issue;
use App\Entity\Notification;
use App\Entity\Tips;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Repository\IssueRepository;
use App\Repository\NotificationRepository;
use App\Repository\TipsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxUserController extends Controller {

  /**
   * @Route("/ajax/userNameChange", name="user_name_change")
   */
  public function changeName(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $googleId = $request->cookies->get('googleId');
      $userId = $request->cookies->get('clientId');

      /** @var UserRepository $userRepository */
      $userRepository = $this->getDoctrine()->getRepository(User::class);
      if(sizeof($googleId) > 0)
        $userRepository->loadUserByGoogleId($googleId);
      else
        $userRepository->loadUserByUsername($userId);
      $userRepository->updateUsername($name);

      $arrData = ['name' => $name];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/userLanguageChange", name="user_language_change")
   */
  public function changeLanguage(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $lang = $request->request->get('language');

      /** @var UserRepository $userRepository */
      $userRepository = $this->getDoctrine()->getRepository(User::class);
      $userRepository->changeLanguage($this->getUser(), $lang);

      $arrData = ['success' => $this->getUser()->getId().",".$lang];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/autocompleteUsername", name="ajax_autocomplete_username")
   */
  public function autocompleteUsername(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $input = $request->request->get('input');

      /** @var UserRepository $userRepository */
      $userRepository = $this->getDoctrine()->getRepository(User::class);
      $result = $userRepository->findUsersBySubstring($input);

      $arrData = ['input' => $input, 'result' => $result];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/entityGetActiveUserlist", name="ajax_entity_userlist")
   */
  public function entityGetUserlist(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $entityId = $request->request->get('entity');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $id = $issueRepository->getIdByLink($entityId);
      if($id === null) { // its Board
        /** @var BoardRepository $boardRepository */
        $boardRepository = $this->getDoctrine()->getRepository(Board::class);
        $board = $boardRepository->getBoardByLink($entityId, $this->getUser());
        $users = $boardRepository->getAllActiveUsers($board);
        $type = 'board';
      }
      else {
        $users = $issueRepository->getAllActiveUsers($id);
        $type = 'issue';
      }
      $render = $this->renderView('user/modal-userlist.html.twig',
        ['name' => $name, 'users' => $users, 'type' => $type]);

      $arrData = ['render' => $render, 'name' => $name, "entity" => $entityId];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/reportBug", name="ajax_bug")
   */
  public function reportBug(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $text = $request->request->get('text');

      $bug = new Bug();
      $bug->setUser($this->getUser());
      $bug->setText($text);
      $bug->setTime();

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->persist($bug);
      $entityManager->flush();

      $arrData = ['text' => $text];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/updateUserNotificationCount", name="ajax_notification_count")
   */
  public function updateUserNotificationCount(Request $request) {
    if ($request->isXmlHttpRequest()) {
      /** @var NotificationRepository $notificationRepository */
      $notificationRepository = $this->getDoctrine()->getRepository(Notification::class);
      $notificationRepository->updateNotificationCount($this->getUser());

      $arrData = ['done' => 'true'];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/userHideAllTips", name="ajax_user_tips_hide")
   */
  public function userHideAllTips(Request $request) {
    if ($request->isXmlHttpRequest()) {
      /** @var TipsRepository $tipsRepository */
      $tipsRepository = $this->getDoctrine()->getRepository(Tips::class);
      /** @var User $user */
      $user = $this->getUser();
      $tipsRepository->hideAllTips($user->getAnonymousLink());

      $arrData = ['done' => 'true'];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/userHideOneTip", name="ajax_user_tip_hide")
   */
  public function userHideOneTip(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $text = $request->request->get('tip');

      /** @var TipsRepository $tipsRepository */
      $tipsRepository = $this->getDoctrine()->getRepository(Tips::class);
      /** @var User $user */
      $user = $this->getUser();
      $tipsRepository->hideOneTip($text, $user->getAnonymousLink());

      $arrData = ['done' => 'true', 'element' => $text, 'user' => $user->getAnonymousLink()];
      return new JsonResponse($arrData);
    }
  }
}