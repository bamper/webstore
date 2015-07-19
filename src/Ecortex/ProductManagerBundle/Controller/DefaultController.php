<?php

namespace Ecortex\ProductManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('EcortexPMBundle:Default:index.html.twig', array('name' => $name));
    }
}
