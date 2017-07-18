<?php

namespace FL\ReportsBundle\Model\DoctrineORM;

use Doctrine\ORM\Mapping as ORM;
use FL\ReportsBundle\Model\ReportRuleSetInterface;
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
     * @var ReportRuleSetInterface[]
     * @ORM\Column(type="object", nullable=false)
     */
    protected $ruleSets;

    /**
     * @var string[]
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $columns = [];

    /**
     * @var string[]
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $sortColumns = [];

    public function setRuleSets(array $ruleSets)
    {
        // ensure changes are persisted even if array size stays the same
        // @see https://stackoverflow.com/a/13231876/2106834
        if (!empty($ruleSets) && $ruleSets === $this->ruleSets) {
            reset($ruleSets);
            $ruleSets[key($ruleSets)] = clone current($ruleSets);
        }

        return parent::setRuleSets($ruleSets);
    }
}
