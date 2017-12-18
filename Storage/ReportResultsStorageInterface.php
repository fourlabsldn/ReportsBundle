<?php

namespace FL\ReportsBundle\Storage;

use FL\QBJSParser\Parsed\AbstractParsedRuleGroup;

interface ReportResultsStorageInterface
{
    /**
     * @param AbstractParsedRuleGroup $ruleGroup
     * @param bool                    $exclude
     *
     * @return $this
     */
    public function addRuleGroup(AbstractParsedRuleGroup $ruleGroup, bool $exclude = false);

    /**
     * @return $this
     */
    public function clearRuleGroups();

    /**
     * @param int|null $currentPage
     * @param int|null $resultsPerPage
     *
     * @return int[]
     */
    public function getIds(int $currentPage = null, int $resultsPerPage = null): array;

    /**
     * @param int|null $currentPage
     * @param int|null $resultsPerPage
     *
     * @return object[]
     */
    public function getResults(int $currentPage = null, int $resultsPerPage = null): array;

    /**
     * @return int
     */
    public function countResults(): int;
}
