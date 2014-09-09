<?php

namespace BiberLtd\Bundle\NewsManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BiberLtdNewsManagementBundle:Default:index.html.twig', array('name' => $name));
    }
}
