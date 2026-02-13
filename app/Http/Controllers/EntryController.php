<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntryController extends Controller
{
    public function ping()
    {
        try {
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getPickupProduct()
    {
        $result = DB::table('pickupproduct')->get()->toArray();

        return $result;
    }

    public function saveProduct(Request $request)
    {
        $data = $request->input();

        if ($data['id'] > 0) {
            DB::table('pickupproduct')
                ->where('id', '=', $data['id'])
                ->update(['name' => $data['name'],
                    'uom' => $data['uom'], 'company' => $data['company']]);
        } else {
            DB::table('pickupproduct')
                ->insert(['name' => $data['name'],
                    'uom' => $data['uom'], 'company' => $data['company']]);
        }
    }

    public function submitPickupUnit(Request $request)
    {
        $data = $request->all();

        $data['date'] = Carbon::createFromDate($data['date'])->format('Y-m-d');
        $timestamp = $data['picked_timestamp'];
        $data['picked_timestamp'] = intval(floor($timestamp / 1000));
        $exists = DB::table('pickupunit')
            ->where('picked_timestamp', '=', $data['picked_timestamp'])
            ->where('idempotency', '=', $data['idempotency'])
            ->get()->toArray();

        if (count($exists) > 0) {
            return true;
        }
        $data['bin'] == null ? $data['bin'] = '' : $data['bin'];
        $data['comment'] == null ? $data['comment'] = '' : $data['comment'];

        DB::table('pickupunit')->insert($data);

        return true;
    }

    public function getPickupUserNames()
    {
        $result = DB::table('pickupUserNames')->select('userNames')->get()->toArray();

        return $result;

    }

    public function saveUserNames(Request $request)
    {
        $data = $request->input();
        $selectID = DB::table('pickupUserNames')->select('id')->get()->toArray();
        $id = $selectID[0]->id;
        DB::table('pickupUserNames')->where('id', '=', $id)
            ->update(['userNames' => $data[0]]);
    }

    public function getPickupUnitRange(Request $request)
    {
        $fields = ! empty($request->fields) ? explode(',', $request->fields) : [];

        $user = $fields[0] ?? null;
        $start = $fields[1] ?? null;
        $end = $fields[2] ?? null;
        $start = Carbon::createFromDate($start)->shiftTimezone('America/Los_Angeles')->timestamp;
        $end = Carbon::createFromDate($end)->shiftTimezone('America/Los_Angeles')->add(1, 'day')->timestamp;
        if ($user == 'admin') {
            $result = DB::table('pickupunit')
                ->where('status', '=', 1)
                ->where('picked_timestamp', '<=', $end)
                ->where('picked_timestamp', '>=', $start)
                ->orderBy('picked_timestamp', 'asc')
                ->get()->toArray();
        } else {
            $result = DB::table('pickupunit')
                ->where('status', '=', 1)
                ->where('user', '=', $user)
                ->where('picked_timestamp', '<=', $end)
                ->where('picked_timestamp', '>=', $start)
                ->orderBy('picked_timestamp', 'asc')
                ->get()->toArray();
        }

        return $result;
    }

    public function saveEntriesEdits(Request $request)
    {
        $data = $request->input();
        foreach ($data as $key => $value) {
            if ($value['changes'][0] == 'removeRow') {
                DB::table('pickupunit')->where('id', '=', $value['id'])
                    ->update(['status' => 0]);
            } else {
                $changes = $value['changes'][0];
                DB::table('pickupunit')->where('id', '=', $value['id'])
                    ->update([$changes[1] => $changes[3]]);
            }
        }
    }

    public function getPickupBin()
    {
        $result = DB::table('pickupbin')->get()->toArray();

        return $result;
    }

    public function saveBin(Request $request)
    {
        $data = $request->input();
        $changes = $data['bin'];
        if ($data['id'] > 0) {
            DB::table('pickupbin')->where('id', '=', $data['id'])
                ->update(['binNumber' => $changes['binNumber'], 'yards' => $changes['yards'],
                    'company' => $changes['company'], 'location' => $changes['location']]);
        } else {
            DB::table('pickupbin')->insert($data['bin']);
        }
    }
}
