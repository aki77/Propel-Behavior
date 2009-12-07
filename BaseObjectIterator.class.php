<?php

class BaseObjectIterator extends IteratorIterator
{
    protected $class;

    public function setClass($class)
    {
        $this->class = $class;
    }

    public function current()
    {
        $object = new $this->class();
        $object->hydrate(parent::current());

        return $object;
    }
}
