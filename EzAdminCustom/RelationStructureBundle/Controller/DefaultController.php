<?php

namespace EzAdminCustom\RelationStructureBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('EzAdminCustomRelationStructureBundle:Default:index.html.twig');
    }
}
