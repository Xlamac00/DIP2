<?php

namespace App\AjaxController;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxUserController extends Controller {

  /**
   * @Route("/testa", name="testa")
   */
  public function testd() {
//    $input = 'ja';
//
//    $userRepository = $this->getDoctrine()->getRepository(User::class);
//    $result = $userRepository->findUsersBySubstring($input);
//
//    $arrData = ['input' => $input, 'result' => $result];
//    die(var_dump($arrData));
  }

  /**
   * @Route("/ajax/autocompleteUsername", name="ajax_autocomplete_username")
   */
  public function autocompleteUsername(Request $request) {
    if ($request->isXmlHttpRequest()) {
      $input = $request->request->get('input');

      $userRepository = $this->getDoctrine()->getRepository(User::class);
      $result = $userRepository->findUsersBySubstring($input);

      $arrData = ['input' => $input, 'result' => $result];
      return new JsonResponse($arrData);
    }
  }

}