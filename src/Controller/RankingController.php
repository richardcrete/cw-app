<?php

namespace App\Controller;

use App\Entity\Coaster;
use App\Entity\Continent;
use App\Entity\Country;
use App\Entity\Manufacturer;
use App\Entity\MaterialType;
use App\Entity\Model;
use App\Entity\SeatingType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RankingController
 * @package App\Controller
 * @Route("/ranking")
 */
class RankingController extends Controller
{
    /**
     * Show ranking of best coasters
     *
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="ranking_index")
     * @Method({"GET"})
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function indexAction(EntityManagerInterface $em)
    {
        $ranking = $em->getRepository('App:Ranking')->findCurrent();

        $nextRankingDate = new \DateTime('first day of next month midnight 1 minute');
        if ($nextRankingDate->diff(new \DateTime('now'), true)->format('%h') < 1) {
            $nextRankingDate = null;
        }

        return $this->render(
            'Ranking/index.html.twig',
            [
                'ranking' => $ranking,
                'nextRankingDate' => $nextRankingDate,
                'filtersForm' => $this->getFiltersForm(),
            ]
        );
    }

    /**
     * @Route(
     *     "/coasters",
     *     name="ranking_search_async",
     *     options = {"expose" = true},
     *     condition="request.isXmlHttpRequest()"
     * )
     * @Method({"GET"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAsyncAction(Request $request)
    {
        $filters = $request->get('filters', []);
        $page = $request->get('page', 1);

        return $this->render(
            'Ranking/results.html.twig',
            [
                'coasters' => $this->getCoasters($filters, $page),
                // array_filter removes empty filters e.g. ['continent' => '']
                'filtered' => count(array_filter($filters, "strlen")) > 0,
            ]
        );
    }

    /**
     * @param array $filters
     * @param int $page
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    private function getCoasters($filters = [], $page = 1)
    {
        $query = $this->getDoctrine()
            ->getRepository('App:Ranking')
            ->findCoastersRanked($filters);

        $paginator = $this->get('knp_paginator');

        return $paginator->paginate(
            $query,
            $page,
            20
        );
    }

    /**
     * Learn more on the ranking
     *
     * @Route("/learn-more", name="ranking_learn_more")
     * @Method({"GET"})
     */
    public function learnMore()
    {
        return $this->render('Ranking/learn_more.html.twig');
    }

    /**
     * Get data to display filter form (mainly <select> data)
     *
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getFiltersForm()
    {
        $cache = new FilesystemAdapter();
        $filtersForm = $cache->getItem('ranking_filters_form');

        if (!$filtersForm->isHit()) {
            $data = [];
            $data['continent'] = $this->getDoctrine()
                ->getRepository(Continent::class)
                ->findBy([], ["name" => "asc"]);

            $data['country'] = $this->getDoctrine()
                ->getRepository(Country::class)
                ->findBy([], ["name" => "asc"]);

            $data['materialType'] = $this->getDoctrine()
                ->getRepository(MaterialType::class)
                ->findBy([], ["name" => "asc"]);

            $data['seatingType'] = $this->getDoctrine()
                ->getRepository(SeatingType::class)
                ->findBy([], ["name" => "asc"]);

            $data['model'] = $this->getDoctrine()
                ->getRepository(Model::class)
                ->findBy([], ['name' => 'asc']);

            $data['manufacturer'] = $this->getDoctrine()
                ->getRepository(Manufacturer::class)
                ->findBy([], ["name" => "asc"]);

            $data['openingDate'] = $this->getDoctrine()
                ->getRepository(Coaster::class)
                ->getDistinctOpeningYears();

            $filtersForm->set($data);
            $filtersForm->expiresAfter(\DateInterval::createFromDateString('7 days'));
            $cache->save($filtersForm);
        }

        return $filtersForm->get();
    }
}