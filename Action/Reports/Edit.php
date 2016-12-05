<?php

namespace FL\ReportsBundle\Action\Reports;

use FL\QBJSParserBundle\Service\JavascriptBuilders;
use FL\ReportsBundle\Storage\ReportStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Edit
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

    public function __invoke(Request $request, string $id): Response
    {
        if (!($report = $this->reportStorage->findOneBy(['id' => $id]))) {
            throw new NotFoundHttpException();
        }

        return new Response($this->twig->render(
            'FLReportsBundle:Reports:edit.html.twig', [
                'builders' => $this->javascriptBuilders->getBuilders(),
                'report' => $report,
            ]
        ));
    }
}
