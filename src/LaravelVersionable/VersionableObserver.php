<?php

namespace RodrigoPedra\LaravelVersionable;

class VersionableObserver
{
    /**
     * @param Versionable $versionable
     */
    public function creating( Versionable $versionable )
    {
        $versionable->setVersionFactory( new VersionFactory( VersionFactory::ACTION_CREATE ) );
    }

    /**
     * @param Versionable $versionable
     */
    public function updating( Versionable $versionable )
    {
        if (!is_null( $versionable->getVersionFactory() )) {
            // is probably deleting or restoring
            return;
        }

        $versionable->setVersionFactory( new VersionFactory( VersionFactory::ACTION_UPDATE ) );
    }

    /**
     * @param Versionable $versionable
     */
    public function deleting( Versionable $versionable )
    {
        $versionable->setVersionFactory( new VersionFactory( VersionFactory::ACTION_DELETE ) );
    }

    /**
     * @param Versionable $versionable
     */
    public function restoring( Versionable $versionable )
    {
        $versionable->setVersionFactory( new VersionFactory( VersionFactory::ACTION_RESTORE ) );
    }

    /**
     * @param Versionable $versionable
     */
    public function saved( Versionable $versionable )
    {
        $this->createNewVersion( $versionable );
    }

    /**
     * @param Versionable $versionable
     */
    public function deleted( Versionable $versionable )
    {
        $this->createNewVersion( $versionable );
    }

    /**
     * Execute the factory's createNewVersion method
     *
     * @param Versionable $versionable
     */
    private function createNewVersion( Versionable $versionable )
    {
        if (is_null( $versionFactory = $versionable->getVersionFactory() )) {
            return;
        }

        $versionFactory->createNewVersion( $versionable );
        $versionable->setVersionFactory( null );
    }
}
