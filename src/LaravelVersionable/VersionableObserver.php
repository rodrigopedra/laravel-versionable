<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Support\Facades\Event;

class VersionableObserver
{
    /**
     * @param Versionable $versionable
     */
    public function creating( Versionable $versionable )
    {
        $versionable->getVersionFactory()->setAction( VersionFactory::ACTION_CREATE );
    }

    /**
     * @param Versionable $versionable
     */
    public function updating( Versionable $versionable )
    {
        $versionFactory = $versionable->getVersionFactory();

        if ($versionFactory->isDeleting() || $versionFactory->isRestoring()) {
            return;
        }

        $versionFactory->setAction( VersionFactory::ACTION_UPDATE );
    }

    /**
     * @param Versionable $versionable
     */
    public function deleting( Versionable $versionable )
    {
        $action = $this->isForceDeleting( $versionable )
            ? VersionFactory::ACTION_DELETE
            : VersionFactory::ACTION_SOFT_DELETE;

        $versionable->getVersionFactory()->setAction( $action );
    }

    /**
     * @param Versionable $versionable
     */
    public function restoring( Versionable $versionable )
    {
        $versionable->getVersionFactory()->setAction( VersionFactory::ACTION_RESTORE );
    }

    /**
     * @param Versionable $versionable
     */
    public function saved( Versionable $versionable )
    {
        if (!$versionable->shouldCreateNewVersion()) {
            return;
        }

        $versionable->getVersionFactory()->createNewVersion();
    }

    /**
     * @param Versionable $versionable
     */
    public function deleted( Versionable $versionable )
    {
        if (!$versionable->shouldCreateNewVersion()) {
            return;
        }

        if ($this->isForceDeleting( $versionable ) && $versionable->shouldPurgeVersionsOnDelete()) {
            $versionable->getVersionFactory()->purgeVersions();

            return;
        }

        $versionable->getVersionFactory()->createNewVersion();
    }

    protected function isForceDeleting( Versionable $versionable )
    {
        return !method_exists( $versionable, 'isForceDeleting' ) || $versionable->isForceDeleting();
    }
}
