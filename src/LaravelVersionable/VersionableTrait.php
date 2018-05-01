<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class VersionableTrait
 *
 * @package RodrigoPedra\LaravelVersionable
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait VersionableTrait
{
    /**
     * Flag that determines if the model allows versioning at all
     *
     * @var bool
     */
    protected $versioningEnabled = true;

    /**
     * Flag that determines if the model shoulkd create a new version on the nexte save/delete/restore
     *
     * @var bool
     */
    protected $forceVersioning = false;

    /**
     * Optional reason, why this version was created
     *
     * @var string
     */
    protected $versioningReason;

    /**
     * Optional data, to be added to model adata when saving version
     *
     * @var string
     */
    protected $versioningData;

    /**
     * Version factory helper
     *
     * @var VersionFactory
     */
    protected $versionFactory;

    /**
     * @return $this
     */
    public function enableVersioning()
    {
        $this->versioningEnabled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableVersioning()
    {
        $this->versioningEnabled = false;

        return $this;
    }

    /**
     * Flag this model to create a versioning on the next save/restore/delete
     *
     * @return $this
     */
    public function forceVersioningOnNextEvent()
    {
        $this->forceVersioning = true;

        return $this;
    }

    /**
     * Unflag this model to create a versioning on the next save/restore/delete
     *
     * @return $this
     */
    public function cancelForceVersioningOnNextEvent()
    {
        $this->forceVersioning = false;

        return $this;
    }

    /**
     * Check if it should create a new version
     *
     * @return bool
     */
    public function shouldCreateNewVersion()
    {
        if ($this->forceVersioning) {
            $this->forceVersioning = false;

            return true;
        }

        if (!$this->versioningEnabled) {
            return false;
        }

        if (!$this->exists) {
            return true;
        }

        $dontVersionFields = $this->getDontVersionFields();

        return count( array_diff_key( $this->getDirty(), array_flip( $dontVersionFields ) ) ) > 0;
    }

    /**
     * Check if it should purge versions on delete
     *
     * @return bool
     */
    public function shouldPurgeVersionsOnDelete()
    {
        return $this->purgeVersionsOnDelete ?? false;
    }

    /**
     * @return VersionFactory
     */
    public function getVersionFactory()
    {
        if (is_null( $this->versionFactory )) {
            /** @var Versionable $this */
            $this->versionFactory = new VersionFactory( $this );
        }

        return $this->versionFactory;
    }

    /**
     * @return array
     */
    public function getDontVersionFields()
    {
        $dontVersionFields = $this->dontVersionFields ?? [];

        return array_merge( $dontVersionFields, [ $this->getUpdatedAtColumn() ] );
    }

    /**
     * Attribute mutator for "versioning_reason"
     * Prevent "versioning_reason" to become a database attribute of model
     *
     * @param string $value
     */
    public function setVersioningReasonAttribute( $value )
    {
        $value = trim( $value );

        $this->versioningReason = empty( $value ) ? null : $value;
    }

    /**
     * Attribute accessor for "versioning_reason"
     * Allows "versioning_reason" to be accessed as a regular attribute
     *
     * @return string|null
     */
    public function getVersioningReasonAttribute()
    {
        return $this->versioningReason;
    }

    /**
     * Attribute mutator for "versioning_data"
     * Prevent "versioning_data" to become a database attribute of model
     *
     * @param mixed $value
     */
    public function setVersioningDataAttribute( $value )
    {
        if (empty( $value )) {
            $this->versioningData = null;

            return;
        }

        $this->versioningData = $value;
    }

    /**
     * Attribute accessor for "versioning_data"
     * Allows "versioning_data" to be accessed as a regular attribute
     *
     * @return mixed
     */
    public function getVersioningDataAttribute()
    {
        return $this->versioningData;
    }

    /**
     * @return string|null
     */
    public function getVersioningReason()
    {
        return $this->versioningReason;
    }

    /**
     * Get model's attributes serialized for versoning
     *
     * @return mixed
     */
    public function serializedAttributesForVersioning()
    {
        $attributes = $this->getAttributes();

        return serialize( $attributes );
    }

    /**
     * Unserialize the model's attributes from versioning
     *
     * @param mixed $serializedAttributes
     *
     * @return $this
     */
    public function unserializeAttributesFromVersoning( $serializedAttributes )
    {
        $attributes = unserialize( $serializedAttributes );

        if (array_key_exists( 'versioning_data', $attributes )) {
            $this->versioningData = $attributes[ 'versioning_data' ];
        } else {
            $this->versioningData = null;
        }

        unset( $attributes[ 'versioning_data' ] );

        $this->forceFill( $attributes );
        $this->exists = true;

        return $this;
    }

    /**
     * Get model's additional data serialized for versoning
     *
     * @return mixed
     */
    public function serializedAdditionalDataForVersioning()
    {
        $additionalData = $this->versioningData;

        if (is_null( $additionalData )) {
            return null;
        }

        return serialize( $additionalData );
    }

    /**
     * Unserialize the model's additional data from versioning
     *
     * @param mixed $serializedData
     *
     * @return $this
     */
    public function unserializeAdditionalDataFromVersoning( $serializedData )
    {
        $additionalData = unserialize( $serializedData );

        $this->versioningData = $additionalData;

        return $this;
    }

    /**
     * Return all versions of the model
     *
     * @return MorphMany
     */
    public function versions()
    {
        return $this->morphMany( Version::class, 'versionable' );
    }

    /**
     * Returns the latest version available
     *
     * @return Version
     */
    public function currentVersion()
    {
        return $this->versions()->orderBy( ( new Version )->getKeyName(), 'DESC' )->first();
    }

    /**
     * Returns the previous version
     *
     * @return Version
     */
    public function previousVersion()
    {
        return $this->versions()->orderBy( ( new Version )->getKeyName(), 'DESC' )->limit( 1 )->offset( 1 )->first();
    }

    /**
     * Get a model based on the version id
     *
     * @param $versionId
     *
     * @return Versionable|null
     */
    public function getVersionModel( $versionId )
    {
        /** @var  Version $version */
        $version = $this->versions()->where( 'version_id', $versionId )->first();

        if (!is_null( $version )) {
            return $version->getModel();
        }

        return null;
    }

    public static function bootVersionableTrait()
    {
        static::observe( VersionableObserver::class );
    }
}
