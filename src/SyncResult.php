<?php

namespace fabkho\doppelganger;

class SyncResult
{
    private string $originalId;
    private string $newId;

    public function __construct($originalId, $newId)
    {
        $this->originalId = $originalId;
        $this->newId = $newId;
    }

    public function getOriginalId()
    {
        return $this->originalId;
    }

    public function getNewId()
    {
        return $this->newId;
    }
}
