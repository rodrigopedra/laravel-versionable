<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Interface Versionable
 *
 * @package RodrigoPedra\LaravelVersionable
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
interface Versionable
{
    /**
     * @return $this
     */
    public function enableVersioning();

    /**
     * @return $this
     */
    public function disableVersioning();

    /**
     * Run a callback where a new version will be created on any save operation despite
     * any versioning criteria
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function forceVersioning( callable $callback );

    /**
     * Check if it should create a new version
     *
     * @return bool
     */
    public function shouldCreateNewVersion();

    /**
     * Check if it should purge versions on delete
     *
     * @return bool
     */
    public function shouldPurgeVersionsOnDelete();

    /**
     * @return VersionFactory
     */
    public function getVersionFactory();

    /**
     * @return array
     */
    public function getDontVersionFields();

    /**
     * @return string|null
     */
    public function getVersioningReason();

    /**
     * Get model's attribute serialized for versoning
     *
     * @return mixed
     */
    public function serializedAttributesForVersioning();

    /**
     * Unserialize the model's attributes from versioning
     *
     * @param mixed $serializedAttributes
     *
     * @return $this
     */
    public function unserializeAttributesFromVersoning( $serializedAttributes );

    /**
     * Get model's additional data serialized for versoning
     *
     * @return mixed
     */
    public function serializedAdditionalDataForVersioning();

    /**
     * Unserialize the model's additional data from versioning
     *
     * @param mixed $serializedData
     *
     * @return $this
     */
    public function unserializeAdditionalDataFromVersoning( $serializedData );

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @see \Illuminate\Database\Eloquent\Concerns\HasAttributes::getDirty()
     *
     * @return array
     */
    public function getDirty();

    /**
     * Return all versions of the model
     *
     * @return MorphMany
     */
    public function versions();

    /**
     * Returns the latest version available
     *
     * @return Version
     */
    public function currentVersion();

    /**
     * Returns the previous version
     *
     * @return Version
     */
    public function previousVersion();

    /**
     * Get a model based on the version id
     *
     * @param $versionId
     *
     * @return $this|null
     */
    public function getVersionModel( $versionId );

}
