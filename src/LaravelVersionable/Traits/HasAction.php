<?php

namespace RodrigoPedra\LaravelVersionable\Traits;

use RodrigoPedra\LaravelVersionable\VersionFactory;

trait HasAction
{
    /**
     * @var string|null
     */
    protected $action;

    /**
     * @return bool
     */
    public function isCreating()
    {
        return $this->action === VersionFactory::ACTION_CREATE;
    }

    /**
     * @return bool
     */
    public function isUpdating()
    {
        return $this->action === VersionFactory::ACTION_UPDATE;
    }

    /**
     * @return bool
     */
    public function isDeleting()
    {
        return in_array( $this->action, [ VersionFactory::ACTION_DELETE, VersionFactory::ACTION_SOFT_DELETE ] );
    }

    /**
     * @return bool
     */
    public function isRestoring()
    {
        return $this->action === VersionFactory::ACTION_RESTORE;
    }

    /**
     * @return null|string
     */
    public function getAction()
    {
        return $this->action;
    }
}
