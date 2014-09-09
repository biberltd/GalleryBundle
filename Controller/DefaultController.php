<?php

namespace BiberLtd\Bundle\GalleryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BiberLtdGalleryBundle:Default:index.html.twig', array('name' => $name));
    }
}
