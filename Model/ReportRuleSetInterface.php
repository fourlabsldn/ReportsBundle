<?php

namespace FL\ReportsBundle\Model;

interface ReportRuleSetInterface
{
    const TYPE_INCLUDE = 'INCLUDE';

    const TYPE_EXCLUDE = 'EXCLUDE';

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type);

    /**
     * Get JSON string.
     *
     * @return string
     */
    public function getRules(): string;

    /**
     * Set JSON string.
     *
     * @param string $rules
     *
     * @return $this
     */
    public function setRules(string $rules);
}
