<?php

class AkPropelBehaviorSelectIterator extends SfPropelBehaviorBase
{
    public function modifyDatabase()
    {
        foreach ($this->getDatabase()->getTables() as $table) {
            $behaviors = $table->getBehaviors();

            if (!isset($behaviors['select_iterator'])) {
                $behavior = clone $this;
                $table->addBehavior($behavior);
            }
        }
    }

    public function staticMethods()
    {
        if ($this->isDisabled()) {
            return;
        }

        return <<<EOF
/**
 * doSelectIterator
 *
 * @param Criteria $criteria
 * @param PropelPDO $con
 * @return BaseObjectIterator
 */
public static function doSelectIterator(Criteria \$criteria, PropelPDO \$con = null)
{
    \$stmt = {$this->getTable()->getPhpName()}Peer::doSelectStmt(\$criteria, \$con);
    \$stmt->setFetchmode(PDO::FETCH_NUM);
    \$itetator = new BaseObjectIterator(\$stmt);
    \$itetator->setClass({$this->getTable()->getPhpName()}Peer::OM_CLASS);
    return \$itetator;
}
EOF;
    }
}

