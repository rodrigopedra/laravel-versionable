<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class VersionFactory
{
    const ACTION_CREATE  = 'create';
    const ACTION_UPDATE  = 'update';
    const ACTION_DELETE  = 'delete';
    const ACTION_RESTORE = 'restore';

    /**
     * Tells which action is being performed (create, update, delete, restore)
     *
     * @var string
     */
    private $action;

    public function __construct( string $action )
    {
        $this->action = $action;
    }

    /**
     * Create a new model's version
     *
     * @param Versionable $versionable
     *
     * @return Version|null
     */
    public function createNewVersion( Versionable $versionable )
    {
        if (!$versionable->shouldCreateNewVersion()) {
            return null;
        }

        /** @var Version $version */
        $version = $versionable->versions()->create( [
            'user_id'    => $this->getAuthUserId(),
            'action'     => $this->action,
            'model_data' => $versionable->serializedAttributesForVersioning(),
            'reason'     => $versionable->getVersioningReason(),
            'url'        => App::runningInConsole() ? 'console' : Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header( 'User-Agent' ),
        ] );

        return $version;
    }

    /**
     * @return int|null
     */
    private function getAuthUserId()
    {
        if (Auth::check()) {
            return Auth::id();
        }

        return null;
    }
}
