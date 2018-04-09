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
      return $this->showDefaultHomepage();
    }
    elseif(sizeof($boards) == 1 or sizeof($boards['favorite']) == 1) {
      if(sizeof($boards) == 1)
        return $this->showSingleBoard($boards['boards'][0]);
      else
        return $this->showSingleBoard($boards['favorite'][0]);
    }
    else { // user has more than one board
      return $this->showMoreBoards($boards);
    }
  }

  /**
   * @Route("/dashboard", name="dashboard")
   */
  public function dashboard() {
    /** @var BoardRoleRepository $boardRole */
    $boardRole = $this->getDoctrine()->getRepository(BoardRole::class);
    /** @var BoardRole[][] $boards */
    $boards = $boardRole->getUserBoardsAndFavorite($this->getUser());
    if(sizeof($boards) == 0) { // user has nothing yet
      return $this->showDefaultHomepage();
    }
    elseif(sizeof($boards) == 1) {
      return $this->showSingleBoard($boards['boards'][0]);
    }
    else { // user has more than one board
      return $this->showMoreBoards($boards);
    }
  }

  private function showDefaultHomepage() {
    return $this->render('dashboard/homepage.html.twig');
  }

  /** @param BoardRole $board */
  private function showSingleBoard($board) {
    return $this->redirectToRoute('board', array('link' => $board->getBoard()->getPageId(),
                                                 'name' => $board->getBoard()->getLink()));
  }

  /** @param BoardRole[][] $boards */
  private function showMoreBoards($boards) {
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