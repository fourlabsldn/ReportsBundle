<?php

namespace FL\ReportsBundle\Model;

class ReportRuleSet implements ReportRuleSetInterface, \Serializable, \JsonSerializable
{
    /**
     * @var string
     */
    protected $type = self::TYPE_INCLUDE;

    /**
     * @var string
     */
    protected $rules = '{"condition":"AND","rules":[],"valid":true}';

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRules(): string
    {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     */
    public function setRules(string $rules)
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return json_encode([$this->type, $this->rules]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->type, $this->rules) = json_decode($serialized, true);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'rules' => $this->rules,
        ];
    }
}
