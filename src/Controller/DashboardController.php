<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DashboardController extends Controller {

  /**
   * @Route("/", name="homepage")
   */
  public function index() {
    /** @var BoardRoleRepository $boardRole */
    $boardRole = $this->getDoctrine()->getRepository(BoardRole::class);
    /** @var BoardRole[][] $boards */
    $boards = $boardRole->getUserBoardsAndFavorite($this->getUser());
    if(sizeof($boards) == 0) { // user has nothing yet
      return $this->render('dashboard/homepage.html.twig');
    }
    elseif(sizeof($boards) == 1) {
      return $this->redirectToRoute('board', array('link' => $boards['boards'][0]->getBoard()->getPageId(),
                                                   'name' => $boards['boards'][0]->getBoard()->getLink()));
    }
    else { // user has more than one board
      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      foreach($boards['boards'] as $board) { // get all users that contributed to all boards
        $active = $boardRepository->getAllActiveUsers($board->getBoard(), 4);
        $board->getBoard()->setActiveUsers($active);
      }
      return $this->render('dashboard/homepage.html.twig',
        ['boards' => $boards['boards'], 'favorite' => $boards['favorite']]);
    }
  }
}