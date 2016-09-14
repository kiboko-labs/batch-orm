<?php

namespace Kiboko\Component\BatchORM\Persister;

interface PersisterInterface
{
    /**
     * @param object $object
     */
    public function persist($object);

    /**
     * @return \Traversable
     */
    public function flush();
}
