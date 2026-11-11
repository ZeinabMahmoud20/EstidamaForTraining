<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHallBlockRequest;
use App\Models\Hall;
use App\Models\HallBlock;
use Illuminate\Http\RedirectResponse;

class HallBlockController extends Controller
{
    public function store(StoreHallBlockRequest $request, Hall $hall): RedirectResponse
    {
        $data = $request->validated();
        $data['start_at'] = str_replace('T', ' ', (string) $data['start_at']);
        $data['end_at'] = str_replace('T', ' ', (string) $data['end_at']);

        $hall->hallBlocks()->create($data);

        return back()->with('success', 'تم حجب إتاحة القاعة للفترة المحددة.');
    }

    public function destroy(Hall $hall, HallBlock $block): RedirectResponse
    {
        abort_unless((int) $block->hall_id === (int) $hall->id, 404);

        $block->delete();

        return back()->with('success', 'تم حذف فترة حجب الإتاحة.');
    }
}
