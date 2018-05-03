<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Mockery as m;
use RodrigoPedra\LaravelVersionable\Versionable;
use RodrigoPedra\LaravelVersionable\VersionableTrait;
use RodrigoPedra\LaravelVersionable\VersionFactory;

class VersionableTest extends VersionableTestCase
{
    public function setUp()
    {
        parent::setUp();

        TestVersionableUser::flushEventListeners();
        TestVersionableUser::boot();

        TestVersionableSoftDeleteUser::flushEventListeners();
        TestVersionableSoftDeleteUser::boot();

        TestPartialVersionableUser::flushEventListeners();
        TestPartialVersionableUser::boot();
    }

    public function tearDown()
    {
        m::close();
        Auth::clearResolvedInstances();
    }

    public function testVersionableRelation()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertInstanceOf( TestVersionableUser::class, $version->versionable );
    }

    public function testInitialSaveShouldCreateVersion()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( 1, $user->versions()->count() );
    }

    public function testRetrievePreviousVersionFails()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( 1, $user->versions()->count() );
        $this->assertNull( $user->previousVersion() );
    }

    public function testRetrievePreviousVersionExists()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();
        // Needed because otherwise timestamps are exactly the same
        sleep( 1 );

        $user->name = 'John';
        $user->save();

        $this->assertEquals( 2, $user->versions()->count() );
        $this->assertNotNull( $user->previousVersion() );

        $this->assertEquals( 'Rodrigo', $user->previousVersion()->getModel()->name );
    }

    public function testVersionAndModelAreEqual()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();
        $this->assertEquals( $user->attributesToArray(), $version->getModel()->attributesToArray() );
    }

    public function testVersionsAreRelatedToUsers()
    {
        $userId = rand( 1, 100 );

        Auth::shouldReceive( 'check' )
            ->andReturn( true );

        Auth::shouldReceive( 'id' )
            ->andReturn( $userId );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();

        $this->assertEquals( $userId, $version->user_id );
    }

    public function testGetResponsibleUserAttribute()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( true );

        Auth::shouldReceive( 'id' )
            ->andReturn( 1 );

        $responsibleOrigUser             = new TestVersionableUser;
        $responsibleOrigUser->name       = 'Rodrigo';
        $responsibleOrigUser->email      = 'rodrigo@example.com';
        $responsibleOrigUser->password   = '12345';
        $responsibleOrigUser->last_login = $responsibleOrigUser->freshTimestamp();
        $responsibleOrigUser->save();

        // Needed because otherwise timestamps are exactly the same
        sleep( 1 );

        App::shouldReceive( 'make' )
            ->once()
            ->with( 'auth.driver' )
            ->andReturn( new TestAuthGuard );

        $user             = new TestVersionableUser;
        $user->name       = 'John';
        $user->email      = 'j.tester@example.com';
        $user->password   = '67890';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $version = $user->currentVersion();

        $responsibleUser = $version->responsible_user;
        $this->assertEquals( $responsibleUser->getKey(), 1 );
        $this->assertEquals( $responsibleUser->name, $responsibleOrigUser->name );
        $this->assertEquals( $responsibleUser->email, $responsibleOrigUser->email );
    }

    public function testDontVersionEveryAttribute()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestPartialVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( 1, $user->versions()->count() );
    }

    public function testVersionEveryAttribute()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( 2, $user->versions()->count() );
    }

    public function testCheckForVersioningEnabled()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user = new TestVersionableUser;
        $user->disableVersioning();

        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( 0, $user->versions()->count() );

        $user->enableVersioning();
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( 1, $user->versions()->count() );
    }

    public function testCheckForVersioningDisabledLaterOn()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user = new TestVersionableUser;

        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();
        $user->disableVersioning();

        $this->assertEquals( 1, $user->versions()->count() );

        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( 1, $user->versions()->count() );
    }

    public function testCanRevertVersion()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user = new TestVersionableUser;

        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $userId = $user->getKey();

        $user->name = 'John';
        $user->save();

        $newUser = TestVersionableUser::find( $userId );
        $this->assertEquals( 'John', $newUser->name );

        // Fetch first version and revert ist
        $newUser->versions()->first()->revert();

        $newUser = TestVersionableUser::find( $userId );
        $this->assertEquals( 'Rodrigo', $newUser->name );
    }

    public function testCanRevertSoftDeleteVersion()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user = new TestVersionableSoftDeleteUser;

        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $userId = $user->getKey();

        $user->name = 'John';
        $user->save();

        $newUser = TestVersionableSoftDeleteUser::find( $userId );
        $this->assertEquals( 'John', $newUser->name );

        // Fetch first version and revert it
        $reverted = $newUser->versions()->first()->revert();

        $newUser = TestVersionableSoftDeleteUser::find( $userId );
        $this->assertEquals( 'Rodrigo', $reverted->name );
        $this->assertEquals( 'Rodrigo', $newUser->name );
    }

    public function testGetVersionModel()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        // Create 3 versions
        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = 'John';
        $user->save();

        $user->name = 'Marcel';
        $user->save();

        $this->assertEquals( 3, $user->versions()->count() );

        $this->assertEquals( 'Rodrigo', $user->getVersionModel( 1 )->name );
        $this->assertEquals( 'John', $user->getVersionModel( 2 )->name );
        $this->assertEquals( 'Marcel', $user->getVersionModel( 3 )->name );
        $this->assertEquals( null, $user->getVersionModel( 4 ) );
    }

    public function testUseReasonAttribute()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user                    = new TestVersionableUser;
        $user->name              = 'Rodrigo';
        $user->email             = 'rodrigo@example.com';
        $user->password          = '12345';
        $user->last_login        = $user->freshTimestamp();
        $user->versioning_reason = 'Doing tests';
        $user->save();

        $this->assertEquals( 'Doing tests', $user->currentVersion()->reason );
    }

    public function testDiffTwoVersions()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep( 1 );

        $user->name = 'John';
        $user->save();

        $diff = $user->previousVersion()->diff();
        $this->assertTrue( is_array( $diff ) );

        $this->assertCount( 1, $diff );
        $this->assertEquals( 'John', $diff[ 'name' ] );
    }

    public function testDiffIgnoresTimestamps()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableIgnoresTimestampUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep( 1 );

        $user->name       = 'John';
        $user->created_at = Carbon::now();
        $user->updated_at = Carbon::now();
        $user->deleted_at = Carbon::now();
        $user->save();

        $diff = $user->previousVersion()->diff();
        $this->assertTrue( is_array( $diff ) );

        $this->assertCount( 1, $diff );
        $this->assertEquals( 'John', $diff[ 'name' ] );
    }

    public function testDiffSpecificVersions()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        // Create 3 versions
        $user             = new TestVersionableSoftDeleteUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();
        sleep( 1 );

        $user->name  = 'John';
        $user->email = 'john@example.com';
        $user->save();
        sleep( 1 );

        $user->name = 'Julia';
        $user->save();

        $diff = $user->currentVersion()->diff( $user->versions()->orderBy( 'version_id', 'ASC' )->first() );
        $this->assertTrue( is_array( $diff ) );

        $this->assertCount( 2, $diff );
        $this->assertEquals( 'Rodrigo', $diff[ 'name' ] );
        $this->assertEquals( 'rodrigo@example.com', $diff[ 'email' ] );

        $diff = $user->currentVersion()->diff( $user->versions()->orderBy( 'version_id', 'ASC' )->offset( 1 )
            ->first() );
        $this->assertTrue( is_array( $diff ) );

        $this->assertCount( 1, $diff );
        $this->assertEquals( 'John', $diff[ 'name' ] );
    }

    public function testVersionsHaveCorrectActions()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionableSoftDeleteUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $this->assertEquals( VersionFactory::ACTION_CREATE, $user->currentVersion()->action );

        $user->name = 'John';
        $user->save();

        $this->assertEquals( VersionFactory::ACTION_UPDATE, $user->currentVersion()->action );

        $user->delete();

        $this->assertEquals( VersionFactory::ACTION_SOFT_DELETE, $user->currentVersion()->action );

        $user->restore();

        $this->assertEquals( VersionFactory::ACTION_RESTORE, $user->currentVersion()->action );

        $user->forceDelete();

        $this->assertEquals( VersionFactory::ACTION_DELETE, $user->currentVersion()->action );
    }

    public function testVersionsArePurgedOnDelete()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user             = new TestVersionablePurgeVersionsOnDeleteUser;
        $user->name       = 'Rodrigo';
        $user->email      = 'rodrigo@example.com';
        $user->password   = '12345';
        $user->last_login = $user->freshTimestamp();
        $user->save();

        $user->name = 'John';
        $user->save();

        $user->delete();

        $this->assertEquals( 3, $user->versions()->count() );

        $user->forceDelete();

        $this->assertEquals( 0, $user->versions()->count() );
    }

    public function testSavesVersioningData()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user                  = new TestVersionableUser;
        $user->name            = 'Rodrigo';
        $user->email           = 'rodrigo@example.com';
        $user->password        = '12345';
        $user->last_login      = $user->freshTimestamp();
        $user->versioning_data = [ 'a' => 1 ];
        $user->save();

        $user = $user->currentVersion()->getModel();

        $this->assertArrayNotHasKey( 'versioning_data', $user->getAttributes() );
        $this->assertEquals( $user->versioning_data, [ 'a' => 1 ] );
    }

    public function testForceVersioning()
    {
        Auth::shouldReceive( 'check' )
            ->andReturn( false );

        $user                  = new TestVersionableUser;
        $user->name            = 'Rodrigo';
        $user->email           = 'rodrigo@example.com';
        $user->password        = '12345';
        $user->last_login      = $user->freshTimestamp();
        $user->versioning_data = [ 'a' => 1 ];
        $user->save();

        // touch does not create a new version as it only updates the updated_at attribute
        $user->touch();

        $this->assertEquals( 1, $user->versions()->count() );

        $user->forceVersioning( function ( TestVersionableUser $user ) {
            // when using this callback a new version is created
            // regardless of any other configuration
            $user->touch();
        } );

        $this->assertEquals( 2, $user->versions()->count() );

        // force versioning should be disabled outside the callback
        $user->touch();

        $this->assertEquals( 2, $user->versions()->count() );
    }
}

/**
 * Class TestVersionableUser
 *
 * @property string $versioningReason
 */
class TestVersionableUser extends Model implements Versionable
{
    use VersionableTrait;

    protected $table = 'users';
}

class TestVersionableSoftDeleteUser extends Model implements Versionable
{
    use VersionableTrait;
    use SoftDeletes;

    protected $table = 'users';
}

class TestPartialVersionableUser extends Model implements Versionable
{
    use VersionableTrait;

    protected $table = 'users';

    protected $dontVersionFields = [ 'last_login' ];
}

class TestVersionableIgnoresTimestampUser extends Model implements Versionable
{
    use VersionableTrait;
    use SoftDeletes;

    protected $table = 'users';

    protected $dontVersionFields = [ 'created_at', 'deleted_at' ];
}

class TestVersionablePurgeVersionsOnDeleteUser extends Model implements Versionable
{
    use VersionableTrait;
    use SoftDeletes;

    protected $table = 'users';

    protected $purgeVersionsOnDelete = true;
}

class TestAuthGuard
{
    public function getProvider()
    {
        return new class
        {
            public function createModel()
            {
                return new TestVersionableUser;
            }
        };
    }
}
