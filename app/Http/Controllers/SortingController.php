<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SortingController extends Controller
{
    public function getPickupSortingProduct()
    {
        $result = DB::table('pickup_sorting_product')->get()->toArray();

        return $result;
    }

    public function submitPickupSortingProduct(Request $request)
    {
        $data = $request->all();

        $data['date'] = Carbon::createFromDate($data['date'])->format('Y-m-d');
        $timestamp = $data['picked_timestamp'];
        $data['picked_timestamp'] = intval(floor($timestamp / 1000));

        $exists = DB::table('pickup_sorting')
            ->where('picked_timestamp', '=', $data['picked_timestamp'])
            ->where('idempotency', '=', $data['idempotency'])
            ->get()->toArray();
        if (count($exists) > 0) {
            return true;
        }

        DB::table('pickup_sorting')->insert($data);

    }

    public function getSortingProductRange(Request $request)
    {
        $fields = ! empty($request->fields) ? explode(',', $request->fields) : [];

        $user = $fields[0] ?? null;
        $start = $fields[1] ?? null;
        $end = $fields[2] ?? null;

        $start = Carbon::createFromDate($start)->shiftTimezone('America/Los_Angeles')->timestamp;
        $end = Carbon::createFromDate($end)->shiftTimezone('America/Los_Angeles')->add(1, 'day')->timestamp;

        if ($user == 'admin') {
            $result = DB::table('pickup_sorting')
                ->where('status', '=', 1)
                ->where('picked_timestamp', '<=', $end)
                ->where('picked_timestamp', '>=', $start)
                ->orderBy('picked_timestamp', 'asc')
                ->get()->toArray();
        } else {
            $result = DB::table('pickup_sorting')
                ->where('status', '=', 1)
                ->where('user', '=', $user)
                ->where('picked_timestamp', '<=', $end)
                ->where('picked_timestamp', '>=', $start)
                ->orderBy('picked_timestamp', 'asc')
                ->get()->toArray();
        }
        foreach ($result as $key => $value) {
            $value->date = Carbon::CreateFromDate($value->date)->format('m/d/Y');
        }

        return $result;
    }

    public function saveSortingProduct(Request $request)
    {
        $data = $request->input();

        if ($data['id'] > 0) {
            DB::table('pickup_sorting_product')->where('id', '=', $data['id'])
                ->update(['name' => $data['name']]);
        } else {
            DB::table('pickup_sorting_product')->insert(['name' => $data['name']]);
        }

    }

    public function saveSortingEdits(Request $request)
    {
        $data = $request->input();
        foreach ($data as $key => $value) {
            $changes = $value['changes'][0];
            DB::table('pickup_sorting')->where('id', '=', $value['id'])
                ->update([$changes[1] => $changes[3]]);
        }
    }
}
