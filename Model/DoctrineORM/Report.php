<?php

namespace FL\ReportsBundle\Model\DoctrineORM;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use FL\ReportsBundle\Model\Report as BaseReport;

/**
 * @ORM\MappedSuperclass
 */
class Report extends BaseReport
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=128, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max="128")
     */
    protected $reportName = '';

    /**
     * @var string
     * @ORM\Column(type="string", length=256, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max="256")
     */
    protected $reportBuilderId = '';

    /**
     * @var string
     * @ORM\Column(type="string", length=256, nullable=false)
     * @Assert\NotBlank
     * @Assert\Length(max="256")
     */
    protected $reportBuilderHumanReadableName = '';

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank
     */
    protected $rulesJsonString = '';

    /**
     * @var string[]
     * @ORM\Column(type="array", nullable=true)
     */
    protected $columns = [];

    /**
     * @var string[]
     * @ORM\Column(type="array", nullable=true)
     */
    protected $sortColumns = [];
}
