<?php

namespace Kiboko\Component\BatchORM;

interface RDBMSConfigurationManagerInterface
{


    /**
     * @param string[] $desiredModes
     * @return string[]
     */
    public function addSessionModes(array $desiredModes);

    /**
     * @param string[] $excludedModes
     * @return string[]
     */
    public function removeSessionModes(array $excludedModes);

    /**
     * @param string[] $originalModes
     */
    public function restoreSessionModes(array $originalModes);
}
