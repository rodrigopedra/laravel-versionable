<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class VersionableTrait
 *
 * @package RodrigoPedra\LaravelVersionable
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
     * Optional reason, why this version was created
     *
     * @var string
     */
    protected $versioningReason;

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
     * Check if it should create a new version
     *
     * @return bool
     */
    public function shouldCreateNewVersion()
    {
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
        $this->versioningReason = $value;
    }

    /**
     * @return string|null
     */
    public function getVersioningReason()
    {
        $versioningReason = trim( $this->versioningReason );

        if (empty( $versioningReason )) {
            return null;
        }

        return $versioningReason;
    }

    /**
     * Get model's attributes serialized for versoning
     *
     * @return mixed
     */
    public function serializedAttributesForVersioning()
    {
        return serialize( $this->getAttributes() );
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
        $this->forceFill( unserialize( $serializedAttributes ) );
        $this->exists = true;

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
