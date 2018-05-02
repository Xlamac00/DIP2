<?php

namespace App\Controller;

use App\Entity\Deadline;
use App\Entity\GaugeChanges;
use App\Entity\Issue;
use App\Entity\Notification;
use App\Entity\Tips;
use App\Entity\User;
use App\Repository\DeadlineRepository;
use App\Repository\GaugeChangesRepository;
use App\Repository\IssueRepository;
use App\Repository\NotificationRepository;
use App\Repository\TipsRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IssueController extends Controller {

  /**
   * @Route("/i/{link}/{name}", name="issue")
   */
  public function index($link, $name) {
    /** @var IssueRepository $issueRepository */
    $issueRepository = $this->getDoctrine()->getRepository(Issue::class);
    $issue = $issueRepository->getIssueByLink($link, $this->getUser());
    // During first access via the link, the order of operations is kind of messed up, so set user rights correctly
    if($issue->getThisUserRights() === null) {
      // force Board entity reload with new user rights
      $issue = $issueRepository->getIssue($issue->getId(), $this->getUser(), true);
    }
    $gaugeCount = $issueRepository->getNumberOfGauges();
    /** @var GaugeChangesRepository $gaugeChangesRepository */
    $gaugeChangesRepository = $this->getDoctrine()->getRepository(GaugeChanges::class);
    $changes = $gaugeChangesRepository->getAllChangesForIssue($issue->getId());
    $users = $issueRepository->getAllActiveUsers($issue->getId());

    /** @var DeadlineRepository $deadlineRepository */
    $deadlineRepository = $this->getDoctrine()->getRepository(Deadline::class);
    $deadlines = $deadlineRepository->getDeadlinesForIssue($issue);

    /** @var NotificationRepository $notificationRepository */
    $notificationRepository = $this->getDoctrine()->getRepository(Notification::class);
    $notifications = $notificationRepository->getUnreadNotifications($this->getUser());

    /** @var TipsRepository $tipsRepository */
    $tipsRepository = $this->getDoctrine()->getRepository(Tips::class);
    /** @var User $user */
    $user = $this->getUser();
    $tips = $tipsRepository->getNewTipsForPage('issue', $user->getAnonymousLink());

    return $this->render('issue/issue-detail.html.twig',
      ["issue" => $issue, "changes" => $changes, "gaugeCount" => $gaugeCount,
       "users" => array_reverse($users), "deadlines" => $deadlines,
       'notifications' => $notifications, "tips" => $tips]);
  }

}
