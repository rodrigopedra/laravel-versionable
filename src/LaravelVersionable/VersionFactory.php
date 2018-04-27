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
     * @var Versionable
     */
    protected $versionable;

    /**
     * @var string|null
     */
    protected $action = null;

    public function __construct( Versionable $versionable )
    {
        $this->versionable = $versionable;
    }

    public function setAction( $action )
    {
        $this->action = $action;
    }

    public function hasAction()
    {
        return !is_null( $this->action );
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
            $this->action = null;

            return null;
        }

        /** @var Version $version */
        $version = $versionable->versions()->create( [
            'user_id'    => $this->getAuthUserId(),
            'action'     => $this->action,
            'model_data' => $versionable->serializedAttributesForVersioning(),
            'reason'     => $versionable->getVersioningReason(),
            'url'        => $this->getRequestUrl(),
            'ip_address' => $this->getRequestIp(),
            'user_agent' => $this->getRequestUserAgent(),
        ] );

        $this->action = null;

        return $version;
    }

    /**
     * @return int|null
     */
    protected function getAuthUserId()
    {
        if (Auth::check()) {
            return Auth::id();
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getRequestUrl()
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return Request::fullUrl();
    }

    /**
     * @return string|null
     */
    protected function getRequestIp()
    {
        if (App::runningInConsole()) {
            return null;
        }

        return Request::ip();
    }

    /**
     * @return string|null
     */
    protected function getRequestUserAgent()
    {
        if (App::runningInConsole()) {
            return null;
        }

        return Request::header( 'User-Agent' );
    }
}
