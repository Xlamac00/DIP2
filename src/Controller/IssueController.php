<?php

namespace App\Controller;

use App\Entity\Gauge;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Repository\IssueRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class IssueController extends Controller {

  /**
   * @Route("/i/{link}/{name}", name="issue")
   */
    public function index($link, $name) {
      /** @var IssueRepository $issueRepository */
      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssueByLink($link, $this->getUser());
      $gaugeCount = $issueRepository->getNumberOfGauges();
      $changes = $this->getDoctrine()->getRepository(GaugeChanges::class)->getAllChangesForIssue($issue->getId());
      $users = $issueRepository->getAllActiveUsers($issue->getId());

      return $this->render('issue/issue-detail.html.twig',
        ["issue" => $issue, "changes" => $changes, "gaugeCount" => $gaugeCount, "users" => array_reverse($users)]);
    }

  /**
   * @Route("/ajax/issueUpdate", name="issue_ajax_issueUpdate")
   */
  public function issueUpdate(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue = $request->request->get('issueId');
      $name = $request->request->get('name');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue);
      $issueRepository->updateName($name);

      $arrData = ['name' => $name];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/test", name="test")
   */
  public function test() {
//    $changeRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
//    $arrData = $changeRepository->gaugeCommentSave(14, 'Tiidudu');
//    $render = $this->renderView('issue/comment.html.twig', ['change' => $arrData]);
//      die($render);
  }

  /**
   * @Route("/ajax/issueGraphChange", name="issue_ajax_graphChange")
   */
  public function graphChange(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $number = $request->request->get('gaugeNumber');
      $value = $request->request->get('gaugeValue');
      $issue = $request->request->get('issueId');
      $user = $request->request->get('userId');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue);
      $arrData = $issueRepository->gaugeValueChange($number, $value, $user);
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

      $render = $this->renderView('issue/comment.html.twig', ['change' => $result]);
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
      $issueId = $request->request->get('issueId');
      $userId = $request->request->get('userId');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $gaugeNumber = $issueRepository->getNumberOfGauges($issueId);
      $issue = $issueRepository->getIssue($issueId);

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
      $gaugeRepository->gaugeValueLog(1, $userId);

      $issue = $issueRepository->getIssue($issueId, true);
      $gaugeCount = $issueRepository->getNumberOfGauges();
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'gaugeCount' => $gaugeCount];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueUpdateGauge", name="issue_ajax_gaugeUpdate")
   */
  public function graphUpdateGauge(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue_id = $request->request->get('issueId');
      $gauge_id = $request->request->get('gaugeId');
      $name = $request->request->get('name');
      $color = $request->request->get('color');

      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gaugeRepository->getGauge($gauge_id);
      $gaugeRepository->changeGaugeData($name, $color); // update the gauge

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issue_id, true);

      $changes = $this->getDoctrine()->getRepository(GaugeChanges::class)->getAllChangesForIssue($issue->getId());

      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);
      $comments = $this->renderView('issue/commentsTab.html.twig',['changes' => $changes]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'comments' => $comments,
         'tab' => $tab];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/issueGetGauges", name="issue_ajax_gaugesInfo")
   */
  public function getGaugesInfo(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue_id = $request->request->get('issueId');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issue_id);

      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);
      return new JsonResponse($tab);
    }
  }

  /**
   * @Route("/ajax/issueOneGauge", name="issue_ajax_oneGaugeInfo")
   */
  public function getOneGaugeInfo(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $gauge_id = $request->request->get('gaugeId');

      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gauge = $gaugeRepository->getGauge($gauge_id);

      $tab = $this->renderView('issue/newGaugeTab.html.twig',
        ['name' => $gauge->getName(), 'title' => 'Edit '.$gauge->getName().":",
         'color' => $gauge->getColor(), 'id' => $gauge_id]);
      return new JsonResponse($tab);
    }
  }

  /**
   * @Route("/ajax/issueGaugeDelete", name="issue_ajax_gaugeDelete")
   */
  public function graphDeleteGauge(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $gauge_id = $request->request->get('value1');
      $issue_id = $request->request->get('value2');

      $gaugeRepository = $this->getDoctrine()->getRepository(Gauge::class);
      $gauge = $gaugeRepository->getGauge($gauge_id);

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->remove($gauge);
      $entityManager->flush();

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issue = $issueRepository->getIssue($issue_id, true);
      $issueRepository->updateGaugesIndex();

      $changes = $this->getDoctrine()->getRepository(GaugeChanges::class)->getAllChangesForIssue($issue->getId());
      $gaugeCount = $issueRepository->getNumberOfGauges();

      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);
      $comments = $this->renderView('issue/commentsTab.html.twig',['changes' => $changes]);

      $arrData =
        ['type' => 'gaugeDelete',
         'labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'comments' => $comments,
         'gaugeCount' => $gaugeCount,
         'tab' => $tab];
      return new JsonResponse($arrData);
    }
  }

  /**
   * @Route("/ajax/gaugeChangePosition", name="issue_ajax_gaugeChangePosition")
   */
  public function gaugeChangePosition(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $issue_id = $request->request->get('issueId');
      $gauge_id = $request->request->get('gaugeId');
      $new_position = $request->request->get('position');

      $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
      $issueRepository->getIssue($issue_id);
      $issueRepository->updateGaugesIndex($gauge_id, $new_position);

      $entityManager = $this->getDoctrine()->getManager();
      $entityManager->clear();
      $issue = $issueRepository->getIssue($issue_id, true);
      $labels = $this->renderView('graphs/graph-labels.html.twig',['gauges' => $issue->getGauges()]);
      $colors = $this->renderView('graphs/graph-colors.html.twig',['gauges' => $issue->getGauges()]);
      $values = $this->renderView('graphs/graph-values.html.twig',['gauges' => $issue->getGauges()]);
      $tab = $this->renderView('issue/editGaugeTab.html.twig',['gauges' => $issue->getGauges()]);

      $arrData =
        ['labels' => $labels,
         'colors' => $colors,
         'values' => $values,
         'tab' => $tab];
      return new JsonResponse($arrData);
    }
  }

}
