<?php

namespace FL\ReportsBundle\Action\Reports;

use FL\QBJSParserBundle\Service\JavascriptBuilders;
use Symfony\Component\HttpFoundation\Response;

/**
 * The class name New is reserved
 */
class NewReport
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
     * @param \Twig_Environment $twig
     * @param JavascriptBuilders $javascriptBuilders
     */
    public function __construct(
        \Twig_Environment $twig,
        JavascriptBuilders $javascriptBuilders
    ) {
        $this->twig = $twig;
        $this->javascriptBuilders = $javascriptBuilders;
    }

    /**
     * @return Response
     */
    public function __invoke(): Response
    {
        return new Response($this->twig->render(
            'FLReportsBundle:Reports:new.html.twig', [
                'builders' => $this->javascriptBuilders->getBuilders(),
            ]
        ));
    }
}
