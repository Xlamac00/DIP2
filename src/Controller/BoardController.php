<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Entity\Notification;
use App\Entity\Tips;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use App\Repository\NotificationRepository;
use App\Repository\TipsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class BoardController extends Controller {
  /**
   * @Route("/b/{link}/{name}", name="board")
   */
  public function showBoard($link, $name) {
    /** @var BoardRepository $boardRepository */
    $boardRepository = $this->getDoctrine()->getRepository(Board::class);
    $board = $boardRepository->getBoardByLink($link, $this->getUser());
    // During first access via the link, the order of operations is kind of messed up, so set user rights correctly
    if($board->getThisUserRights() === null) {
      // force Board entity reload with new user rights
      $board = $boardRepository->getBoard($board->getId(), $this->getUser(), true);
    }
    /** @var NotificationRepository $notificationRepository */
    $notificationRepository = $this->getDoctrine()->getRepository(Notification::class);
    $notifications = $notificationRepository->getUnreadNotifications($this->getUser());

    /** @var TipsRepository $tipsRepository */
    $tipsRepository = $this->getDoctrine()->getRepository(Tips::class);
    /** @var User $user */
    $user = $this->getUser();
    $tips = $tipsRepository->getNewTipsForPage('board', $user->getAnonymousLink());

    return $this->render('board/board-overview.html.twig',
      ["board" => $board, 'notifications' => $notifications, "tips" => $tips]);
  }
}