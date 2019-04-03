{!! $phpOpenTag !!}

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Stylemix\Listing\Facades\Entities;

class {{$class}} extends Migration
{
 	/**
 	 * Run the migrations.
 	 *
 	 * @return void
 	 */
 	public function up()
 	{
 	 	Schema::create('{{ $table }}', function (Blueprint $table) {
 	 	 	$table->increments('id');
 	 	 	$table->string('title');
			$table->timestamps();
			$table->entityColumns();
@if ($softDeletes)
 	 	 	$table->softDeletes();
@endif
 	 	});

		Entities::createDataTable('{{ $table }}');
 	}

 	/**
 	 * Reverse the migrations.
 	 *
 	 * @return void
 	 */
 	public function down()
 	{
		Entities::dropDataTable('{{ $table }}');
 	 	Schema::dropIfExists('{{ $table }}');
 	}
}
