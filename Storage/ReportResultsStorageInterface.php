<?php

namespace FL\ReportsBundle\Storage;

use FL\QBJSParser\Parsed\AbstractParsedRuleGroup;

interface ReportResultsStorageInterface
{
    /**
     * @param AbstractParsedRuleGroup $parsedRuleGroup
     * @param int                     $firstResult
     * @param int                     $resultsPerPage
     *
     * @return object[]
     */
    public function resultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup, int $firstResult, int $resultsPerPage): array;

    /**
     * @param AbstractParsedRuleGroup $parsedRuleGroup
     * @param int                     $firstResult
     * @param int                     $resultsPerPage
     *
     * @return int
     */
    public function countResultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup, int $firstResult, int $resultsPerPage): int;
}
