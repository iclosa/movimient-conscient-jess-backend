<?php

use App\Models\WellnessItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        WellnessItem::query()
            ->where('type', 'mindfulness')
            ->whereNull('config')
            ->update(['config' => json_encode(WellnessItem::DEFAULT_MINDFULNESS, JSON_UNESCAPED_UNICODE)]);
    }

    public function down(): void
    {
        WellnessItem::query()
            ->where('type', 'mindfulness')
            ->where('config', json_encode(WellnessItem::DEFAULT_MINDFULNESS, JSON_UNESCAPED_UNICODE))
            ->update(['config' => null]);
    }
};
