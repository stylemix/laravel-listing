{!! $phpOpenTag !!}

namespace {{ $namespace }};

use {{ $rootNamespace }}{{ $model }};
use {{ $rootNamespace }}Http\Forms\{{ $model }}Form;
use {{ $rootNamespace }}Http\Requests\{{ $model }}Request;
use {{ $resourceClassNamespace }}\{{ $model }}Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class {{ $class }} extends Controller
{

	public function __construct()
	{
		$this->middleware('auth', ['only' => ['store', 'edit', 'update', 'destroy']]);
	}

	/**
	 * Display a listing of {{$resource}}.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return mixed
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function index(Request $request)
	{
		$this->authorize('manage', {{ $model }}::class);

		${{ $resource }} = {{ $model }}::search($request)->get();

		return {{ $model }}Resource::collection(${{ $resource }}->paginate());
	}

	/**
	 * Creation form for {{$resource}} resource
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return mixed
	 */
	public function create(Request $request)
	{
		$this->authorize('create', {{ $model }}::class);

		return {{ $model }}Form::make();
	}

	/**
	 * Store a newly created {{$resource}} in storage.
	 *
	 * @param \{{ $rootNamespace }}Http\Requests\{{ $model }}Request $request
	 *
	 * @return mixed
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function store({{ $model }}Request $request)
	{
		$this->authorize('create', {{ $model }}::class);

		${{ $resource }} = $request->fill(new {{ $model }}());

		${{ $resource }}->save();

		return {{ $model }}Resource::make(${{ $resource }});
	}

	/**
	 * Display the specified {{$resource}}.
	 *
	 * @param mixed $id
	 *
	 * @return mixed
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function show($id)
	{
		${{ $resource }} = {{ $model }}::search()->findOrFail($id);

		$this->authorize('view', ${{ $resource }});

		return {{ $model }}Resource::make(${{ $resource }});
    }

	/**
	 * Edit form for {{$resource}} resource
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \{{ $rootNamespace }}{{ $model }} ${{$resource}}
	 *
	 * @return mixed
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function edit(Request $request, {{ $model }} ${{ $resource }})
	{
		$this->authorize('update', ${{ $resource }});

		return {{ $model }}Form::make(${{ $resource }});
	}

	/**
	 * Update the specified {{$resource}} in storage.
	 *
	 * @param \{{ $rootNamespace }}Http\Requests\{{ $model }}Request $request
	 * @param \{{ $rootNamespace }}{{ $model }} $country
	 *
	 * @return mixed
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function update({{ $model }}Request $request, {{ $model }} ${{ $resource }})
	{
		$this->authorize('update', ${{ $resource }});

		$request->fill(${{ $resource }})->save();

		return {{ $model }}Resource::make(${{ $resource }});
	}

	/**
	 * Remove the specified {{$resource}} from storage.
	 *
	 * @param \{{ $rootNamespace }}{{ $model }} $country
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function destroy({{ $model }} ${{ $resource }})
	{
		$this->authorize('delete', ${{ $resource }});

		${{ $resource }}->delete();

		return Response::json([], 202);
	}
}
