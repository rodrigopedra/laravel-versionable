<?php

namespace RodrigoPedra\LaravelVersionable;

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

        if ($versionFactory->hasAction()) {
            // is probably deleting or restoring
            return;
        }

        $versionFactory->setAction( VersionFactory::ACTION_UPDATE );
    }

    /**
     * @param Versionable $versionable
     */
    public function deleting( Versionable $versionable )
    {
        $versionable->getVersionFactory()->setAction( VersionFactory::ACTION_DELETE );
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
        $versionable->getVersionFactory()->createNewVersion( $versionable );
    }

    /**
     * @param Versionable $versionable
     */
    public function deleted( Versionable $versionable )
    {
        $versionable->getVersionFactory()->createNewVersion( $versionable );
    }
}