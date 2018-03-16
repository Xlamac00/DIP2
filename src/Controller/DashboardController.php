<?php

namespace App\Controller;

use App\Entity\Board;
use App\Entity\Issue;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends Controller {

  /**
   * @Route("/", name="homepage")
   */
  public function index() {
    $board = $this->getDoctrine()->getRepository(Board::class)->getBoard(1);

    return $this->render('dashboard/issue-overview.html.twig',
          ["board" => $board]);
  }

  /**
   * @Route("/ajax/issueNew", name="issue_ajax_new")
   */
  public function issueNew(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $board_id = $request->request->get('board');

      $board = $this->getDoctrine()->getRepository(Board::class)->getBoard($board_id);

      $entityManager = $this->getDoctrine()->getManager();
      $issue = new Issue();
      $issue->setName($name);
      $issue->setBoard($board);
      $entityManager->persist($issue);
      $entityManager->flush();

//      $card = $this->renderView('dashboard/issue-card.html.twig',['issue' => $issue]);

      $arrData = [ 'link' => $issue->getLink(),
//        'card' => $card
      ];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueDelete", name="issue_ajax_delete")
   */
  public function issueDelete(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $link = $request->request->get('link');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($link);

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->remove($issue);
      $entityManager->flush();

      $arrData = ['link' => $link];
      return new JsonResponse($arrData);
    }
  }
}