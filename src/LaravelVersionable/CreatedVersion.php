<?php

namespace RodrigoPedra\LaravelVersionable;

use RodrigoPedra\LaravelVersionable\Traits\HasAction;

class CreatedVersion
{
    use HasAction;

    public $versionable;

    public function __construct( Versionable $versionable, $action )
    {
        $this->versionable = $versionable;
        $this->action      = $action;
    }
}
