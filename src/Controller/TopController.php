<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TopController
 * @package App\Controller
 * @Route("/tops")
 */
class TopController extends Controller
{
    /**
     * Displays all tops
     *
     * @Route("/", name="top_list")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository('App:Liste')->findAllTops();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->get('page', 1),
            9,
            ['wrap-queries' => true]
        );

        return $this->render(
            'Top/list.html.twig',
            [
                'listes' => $pagination,
            ]
        );
    }
}