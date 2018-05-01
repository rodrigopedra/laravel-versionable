<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;

/**
 * Class Version
 *
 * @package RodrigoPedra\LaravelVersionable
 */
class Version extends Eloquent
{
    /**
     * @var string
     */
    public $table = 'versions';

    /**
     * @var string
     */
    protected $primaryKey = 'version_id';

    protected $casts = [
        'version_id'     => 'integer',
        'versionable_id' => 'integer',
        'user_id'        => 'integer',
    ];

    protected $fillable = [
        'user_id',
        'action',
        'reason',
        'url',
        'ip_address',
        'user_agent',
        'model_data',
        'additional_data',
    ];

    /**
     * Sets up the relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function versionable()
    {
        return $this->morphTo();
    }

    /**
     * Return the user responsible for this version
     *
     * @return mixed
     */
    public function getResponsibleUserAttribute()
    {
        return App::make( 'auth.driver' )
            ->getProvider()
            ->createModel()
            ->withoutGlobalScopes()
            ->find( $this->user_id );
    }

    /**
     * Return the versioned model
     *
     * @return Versionable
     */
    public function getModel()
    {
        $modelData = is_resource( $this->model_data )
            ? stream_get_contents( $this->model_data )
            : $this->model_data;

        $additionalData = is_resource( $this->additional_data )
            ? stream_get_contents( $this->additional_data )
            : $this->additional_data;

        $modelClass = Relation::getMorphedModel( $this->versionable_type ) ?: $this->versionable_type;

        return tap( new $modelClass, function ( Versionable $versionable ) use ( $modelData, $additionalData ) {
            $versionable->unserializeAttributesFromVersoning( $modelData );
            $versionable->unserializeAdditionalDataFromVersoning( $additionalData );
        } );
    }

    /**
     * Revert to the stored model version make it the current version
     *
     * @return Versionable
     */
    public function revert()
    {
        $model = $this->getModel();

        $dontVersionFields = $model->getDontVersionFields();

        foreach ($dontVersionFields as $field) {
            unset( $model->{$field} );
        }

        $model->save();

        return $model;
    }

    /**
     * Diff the attributes of this version model against another version.
     * If no version is provided, it will be diffed against the current version.
     *
     * @param Version|null $againstVersion
     *
     * @return array
     */
    public function diff( Version $againstVersion = null )
    {
        $model             = $this->getModel();
        $dontVersionFields = $model->getDontVersionFields();

        $diff = $againstVersion
            ? $againstVersion->getModel()
            : $this->versionable()->withoutGlobalScopes()->first()->currentVersion()->getModel();

        $diffArray = array_diff_assoc( $diff->getAttributes(), $model->getAttributes() );

        foreach ($dontVersionFields as $field) {
            if (isset( $diffArray[ $field ] )) {
                unset( $diffArray[ $field ] );
            }
        }

        return $diffArray;
    }
}
