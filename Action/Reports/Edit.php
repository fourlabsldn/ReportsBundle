<?php

namespace FL\ReportsBundle\Action\Reports;

use FL\QBJSParserBundle\Service\JavascriptBuilders;
use FL\ReportsBundle\Storage\ReportsStorageInterface;
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

    public function __invoke(Request $request, string $id): Response
    {
        if (! ($report = $this->reportsStorage->findOneBy(['id'=>$id]))) {
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
