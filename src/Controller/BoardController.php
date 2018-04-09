<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardRole;
use App\Repository\BoardRepository;
use App\Repository\BoardRoleRepository;
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

    return $this->render('board/board-overview.html.twig', ["board" => $board]);
  }
}