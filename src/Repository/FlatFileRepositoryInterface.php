<?php

namespace Kiboko\Component\BatchORM\Repository;

interface FlatFileRepositoryInterface extends RepositoryInterface
{
    /**
     * @return void
     */
    public function cleanup();
}
