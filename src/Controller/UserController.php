<?php
namespace App\Controller;

use App\Entity\Board;
use App\Entity\Deadline;
use App\Entity\Notification;
use App\Repository\BoardRepository;
use App\Repository\DeadlineRepository;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class UserController extends Controller {

  /**
   * @Route("/deadlines", name="myDeadlines")
   */
  public function myDeadlines() {
    /** @var DeadlineRepository $deadlineRepository */
    $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
    $deadlines = $deadlineRepository->getDeadlinesForUser($this->getUser());

    /** @var NotificationRepository $notificationRepository */
    $notificationRepository = $this->getDoctrine()->getRepository(Notification::class);
    $notifications = $notificationRepository->getUnreadNotifications($this->getUser());

    return $this->render('deadline/deadline-overview.html.twig',
      ["deadlines" => $deadlines, 'notifications' => $notifications]);
  }

  /**
   * @Route("/login", name="login")
   */
  public function login() {}

  /**
   * @Route("/login-fail", name="login-fail")
   */
  public function loginFail() {}


  /**
   * @Route("/login/google", name="google")
   */
  public function loginGoogle() {}

  /**
   * @Route("/u/{link}/{name}", name="user_share")
   */
  public function showBoard($link, $name) {
    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->getDoctrine()->getRepository(Board::class);
    $board = $boardRepository->getBoardByLink($link, $this->getUser());
    if($board !== null) { // Its Board
      return $this->redirectToRoute('board', array('link' => $link, 'name' => $name));
    }
    else { // Its Issue
      return $this->redirectToRoute('issue', array('link' => $link, 'name' => $name));
    }
  }
}