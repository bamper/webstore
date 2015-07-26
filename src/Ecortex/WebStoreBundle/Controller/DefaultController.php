<?php

namespace Ecortex\WebStoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('EcortexWebStoreBundle:Default:index.html.twig');
    }
}
