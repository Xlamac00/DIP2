<?php

namespace App\Controller;

use App\Entity\Board;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ErrorController extends Controller {

  /**
   * @Route("/error/{number}", name="error")
   */
  public function index($number) {
    return $this->render('error/'.$number.'.html.twig');
  }
}