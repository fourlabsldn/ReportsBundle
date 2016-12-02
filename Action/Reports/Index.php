<?php

namespace FL\ReportsBundle\Action\Reports;

use FL\QBJSParserBundle\Service\JavascriptBuilders;
use FL\ReportsBundle\Storage\ReportsStorageInterface;
use Symfony\Component\HttpFoundation\Response;

class Index
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var JavascriptBuilders
     */
    protected $javascriptBuilders;

    /**
     * @var ReportsStorageInterface
     */
    protected $reportsStorage;

    /**
     * @param \Twig_Environment $twig
     * @param JavascriptBuilders $javascriptBuilders
     * @param ReportsStorageInterface $reportsStorage
     */
    public function __construct(
        \Twig_Environment $twig,
        JavascriptBuilders $javascriptBuilders,
        ReportsStorageInterface $reportsStorage
    ) {
        $this->twig = $twig;
        $this->javascriptBuilders = $javascriptBuilders;
        $this->reportsStorage = $reportsStorage;
    }

    public function __invoke(): Response
    {
        $reports = $this->reportsStorage->findBy([], ['reportName'=>'ASC']);
        return new Response($this->twig->render('FLReportsBundle:Reports:index.html.twig', [
            'reports' => $reports,
        ]));
    }
}
