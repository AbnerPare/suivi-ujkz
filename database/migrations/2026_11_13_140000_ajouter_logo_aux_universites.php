<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('universites', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('telephone');
        });

        $source = base_path('images/logo-ujkz.png');
        if (! is_file($source)) {
            return;
        }

        Storage::disk('local')->put('logos/ujkz.png', file_get_contents($source));

        $id = DB::table('universites')->where('sigle', 'UJKZ')->value('id')
            ?? DB::table('universites')->where('nom', 'like', '%Ki-Zerbo%')->value('id')
            ?? DB::table('universites')->where('nom', 'like', '%KI-ZERBO%')->value('id');

        if ($id) {
            DB::table('universites')->where('id', $id)->update(['logo' => 'logos/ujkz.png']);
        }
    }

    public function down(): void
    {
        Schema::table('universites', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
