<?php

class AkPropelBehaviorInnodbCount extends SfPropelBehaviorBase
{
    protected $countColumnConstantName;

    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            $behaviors = $table->getBehaviors();

            if (!isset($behaviors['innodb_count'])) {
                $behavior = clone $this;
                $table->addBehavior($behavior);
            }
        }
    }

    public function modifyTable()
    {
        $this->countColumnConstantName = $this->getCountColumnConstantName();

        if (!$this->countColumnConstantName) {
            $this->setParameter('disabled', 'true');
        }
    }

    public function peerFilter(& $script)
    {
        if ($this->isDisabled()) {
            return;
        }

        $class = new sfClassManipulator($script);
        $class->filterMethod('doCount', array($this, 'filterDoCount'));

        $script = $class->getCode();
    }

    public function filterDoCount($line)
    {
        $doCount = 'BasePeer::doCount';

        if (strpos($line, $doCount) !== false) {
            $line = str_replace(
                $doCount,
                $this->getTable()->getPhpName() . 'Peer::_doCount',
                $line
            );
        }

        return $line;
    }

    public function staticMethods()
    {
        if ($this->isDisabled()) {
            return;
        }

        return <<<EOF
/**
 * _doCount
 */
protected static function _doCount(Criteria \$criteria, PropelPDO \$con = null)
{
    \$needsComplexCount = (
        \$criteria->getGroupByColumns() ||
        \$criteria->getOffset() ||
        \$criteria->getLimit() ||
        \$criteria->getHaving() ||
        in_array(Criteria::DISTINCT, \$criteria->getSelectModifiers())
    );
    if (!\$needsComplexCount) {
        \$criteria->
            clearSelectColumns()->
            clearOrderByColumns()->
            addSelectColumn('COUNT(' . {$this->countColumnConstantName} . ')');
        return BasePeer::doSelect(\$criteria, \$con);
    }
    return BasePeer::doCount(\$criteria, \$con);
}
EOF;
    }

    protected function getCountColumnConstantName()
    {
        $parameters = $this->getParameters();

        if (isset($parameters['column'])) {
            if (!$column = $this->getColumnForParameter('column')) {
                throw new Exception('column error: ' . $parameters['column']);
            }
        } else {
            // パラメータが未指定の場合は一番最初のインデックスを使う
            $indices = $this->getTable()->getIndices();

            if (count($indices) < 1) {
                return null;
            }
            $columns = $indices[0]->getColumns();
            $column = $this->getTable()->getColumn($columns[0]);
        }

        return $column->getConstantName();
    }
}
