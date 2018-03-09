<?php

namespace App\Controller;

use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class IssueController extends Controller {

  /**
   * @Route("/issue/{link}", name="issue")
   */
    public function index($link) {
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($link);
      $gaugeNumber = $issueRepository->getNumberOfGauges();
      $changes = $this->getDoctrine()->getRepository(GaugeChanges::class)->getAllChangesForIssue($issue->getId());

      return $this->render('issue/edit.html.twig',
        ["issue" => $issue, "changes" => $changes, "gaugeNumber" => $gaugeNumber]);
    }

  /**
   * @Route("/ajax/issueGraphChange", name="issue_ajax_graphChange")
   */
  public function graphChange(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $number = $request->request->get('gaugeNumber');
      $value = $request->request->get('gaugeValue');
      $issue = $request->request->get('issueId');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue);
      $arrData = $issueRepository->gaugeValueChange($number, $value);
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueGraphDiscard", name="issue_ajax_graphDiscard")
   */
  public function graphChangeDiscard(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issueId');

      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $result = $gaugeRepository->gaugeValueDiscard($issue);

      $arrData =
        ['newValue' => $result['newValue'],
         'position' => $result['position']];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueGraphComment", name="issue_ajax_graphComment")
   */
  public function graphChangeComment(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issueId');
      $text = $request->request->get('text');

      $changeRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
      $result = $changeRepository->gaugeCommentSave($issue, $text);

      $render = $this->renderView('issue/edit-comment.html.twig', ['change' => $result]);
      return new JsonResponse($render);
    }
  }

  /**
   * @Route("/ajax/issueNewGauge", name="issue_ajax_newGauge")
   */
  public function graphNewGauge(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $name = $request->request->get('name');
      $color = $request->request->get('color');
      $issue_id = $request->request->get('issueId');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $gaugeNumber = $issueRepository->getNumberOfGauges($issue_id);
      $issue = $issueRepository->getIssue($issue_id);

      $entityManager = $this->getDoctrine()->getManager();
      $gauge = new Gauge();
      $gauge->setValue(1);
      $gauge->setName($name);
      $gauge->setColor($color);
      $gauge->setIssue($issue);
      $gauge->setPosition($gaugeNumber);
      $entityManager->persist($gauge);
      $entityManager->flush();

      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gaugeRepository->setGauge($gauge);
      $gaugeRepository->gaugeValueLog(1);

      $issue = $issueRepository->getIssue($issue_id, true);
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values];
      return new JsonResponse($arrData);
    }
  }
}
