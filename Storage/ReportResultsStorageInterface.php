<?php

namespace FL\ReportsBundle\Storage;

use FL\QBJSParser\Parsed\AbstractParsedRuleGroup;

interface ReportResultsStorageInterface
{
    /**
     * @param AbstractParsedRuleGroup $parsedRuleGroup
     * @param int|null                $firstResult
     * @param int|null                $resultsPerPage
     *
     * @return object[]
     */
    public function resultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup, int $firstResult = null, int $resultsPerPage = null): array;

    /**
     * @param AbstractParsedRuleGroup $parsedRuleGroup
     *
     * @return int
     */
    public function countResultsFromParsedRuleGroup(AbstractParsedRuleGroup $parsedRuleGroup): int;
}
