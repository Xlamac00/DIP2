<?php
namespace App\AjaxController;

use App\Entity\Issue;
use App\Repository\IssueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxIssueController extends Controller {


  /**
   * @Route("/ajax/issueNew", name="issue_ajax_new")
   */
  public function issueNew(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $board_id = $request->request->get('board');

      /** @var IssueRepository $repo */
      $repo = $this->getDoctrine()->getRepository(Issue::class);
      $link = $repo->createNewIssue($name, $board_id, $this->getUser());

      $arrData = ['link' => $link];
      return new JsonResponse($arrData);
    }
  }

//  /**
//   * @Route("/testi", name="testi")
//   */
//  public function testi() {
//    $link = "3b44ba79";
////    $board_id = 1;
//
//    /** @var IssueRepository $issueRepository */
//    $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
//    $issue = $issueRepository->getIssueByLink($link);
////    $issue->delete();
//    $entityManager = $this->getDoctrine()->getManager();
//    $entityManager->remove($issue);
//    $entityManager->flush();
//
//    $arrData = ['link' => $link];
//    die(var_dump($arrData));
//  }

  /**
   * @Route("/ajax/issueDelete", name="issue_ajax_delete")
   */
  public function issueDelete(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $link = $request->request->get('link');

      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($link, $this->getUser());
      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->remove($issue);
      $entityManager->flush();

      $arrData = ['link' => $link];
      return new JsonResponse($arrData);
    }
  }
}
