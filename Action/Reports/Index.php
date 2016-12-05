<?php

namespace FL\ReportsBundle\Action\Reports;

use FL\QBJSParserBundle\Service\JavascriptBuilders;
use FL\ReportsBundle\Storage\ReportStorageInterface;
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
     * @var ReportStorageInterface
     */
    protected $reportStorage;

    /**
     * @param \Twig_Environment      $twig
     * @param JavascriptBuilders     $javascriptBuilders
     * @param ReportStorageInterface $reportStorage
     */
    public function __construct(
        \Twig_Environment $twig,
        JavascriptBuilders $javascriptBuilders,
        ReportStorageInterface $reportStorage
    ) {
        $this->twig = $twig;
        $this->javascriptBuilders = $javascriptBuilders;
        $this->reportStorage = $reportStorage;
    }

    public function __invoke(): Response
    {
        $reports = $this->reportStorage->findBy([], ['reportName' => 'ASC']);

        return new Response($this->twig->render('FLReportsBundle:Reports:index.html.twig', [
            'reports' => $reports,
        ]));
    }
}
