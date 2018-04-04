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
    /** @var BoardRole[] $boards */
    $boards = $boardRole->getUserBoards($this->getUser());
    if(sizeof($boards) == 0) { // user has nothing yet
      /** @var BoardRepository $boardRepository */
      $boardRepository = $this->getDoctrine()->getRepository(Board::class);
      $board = $boardRepository->getBoard(1);
      if($board == null) {
        return $this->render('homepage.html.twig');
      }
      else
        return $this->render('homepage.html.twig', ['link' => $board->getUrl()."?".$board->getShareLink()]);
    }
    elseif(sizeof($boards) == 1) {
      return $this->redirectToRoute('board', array('link' => $boards[0]->getBoard()->getPageId(),
                                                   'name' => $boards[0]->getBoard()->getLink()));
//      $board = $this->getDoctrine()->getRepository(Board::class)->getBoard(1, $this->getUser());
//      return $this->render('dashboard/board-overview.html.twig', ["board" => $board]);
    }
//    else {}
  }

  /**
   * @Route("/newBoard", name="newBoard")
   */
  public function newBoard() {
    /** @var BoardRepository $board */
    $board = $this->getDoctrine()->getRepository(Board::class);
    $board->createNewBoard('Rockovy koncert', $this->getUser());
    die("Created. Return to previous page.");
  }

  /**
   * @Route("/b/{link}/{name}", name="board")
   */
  public function showBoard($link, $name) {
    $board = $this->getDoctrine()->getRepository(Board::class)->getBoardByLink($link, $this->getUser());

    return $this->render('dashboard/board-overview.html.twig', ["board" => $board]);
  }
}