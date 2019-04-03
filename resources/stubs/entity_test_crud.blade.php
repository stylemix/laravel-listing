{!! $phpOpenTag !!}

namespace Tests\Feature;

use App\{{ $model }};
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stylemix\Listing\Tests\ElasticSearchIndexing;
use Tests\TestCase;

class {{ $class }} extends TestCase
{
	use DatabaseMigrations, ElasticSearchIndexing, WithFaker;

	protected function setUp() : void
	{
		parent::setUp();

		$this->createAdmin();
	}

    public function testIndex()
    {
        $response = $this
			->actingAs($this->admin, 'api')
			->getJson('api{{ $url }}');

        $response->assertStatus(200);
    }

    public function testCreate()
    {
        $response = $this
			->actingAs($this->admin, 'api')
			->getJson('api{{ $url }}/create');

        $response->assertStatus(200);
    }

    public function testStore()
    {
        $response = $this
			->actingAs($this->admin, 'api')
			->postJson('api{{ $url }}', [
				// todo: post test data
			]);

        $response->assertStatus(201);
    }

	public function testEdit()
	{
		${{ $resource }} = factory({{ $model }}::class)->create();

		$response = $this
			->actingAs($this->admin, 'api')
			->getJson('api{{ $url }}/' . ${{ $resource }}->id . '/edit');

		$response->assertStatus(200);
	}

	public function testUpdate()
	{
		${{ $resource }} = factory({{ $model }}::class)->create();

		$response = $this
			->actingAs($this->admin, 'api')
			->putJson('api{{ $url }}/' . ${{ $resource }}->id, [
				// todo: post test data
			]);

		$response->assertStatus(200);
	}

	public function testDelete()
	{
		${{ $resource }} = factory({{ $model }}::class)->create();

		$response = $this
			->actingAs($this->admin, 'api')
			->deleteJson('api{{ $url }}/' . ${{ $resource }}->id);

		$response->assertStatus(202);
	}
}
