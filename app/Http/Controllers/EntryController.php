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
        $result = DB::table('pickup_product')->get()->toArray();

        return $result;
    }

    public function saveProduct(Request $request)
    {
        $data = $request->input();
        $exists = DB::table('pickup_product')->select('id')->where('name', $data['name'])->get()->toArray();
        $id = (array_key_exists('id', $data) ? $data['id'] : -1);
        if (count($exists) > 0 &&  $exists[0]->id != $id) {
            return response()->json(['error' => 'product already exists'], 400);
        }
        else {
            if (array_key_exists('id', $data) && $data['id'] > 0) {
                DB::table('pickup_product')
                    ->where('id', '=', $data['id'])
                    ->update(['name' => $data['name'],
                        'uom' => $data['uom'], 'company' => $data['company']]);
                return 'updated';
            } else {
                $newID = DB::table('pickup_product')
                    ->insertGetId(['name' => $data['name'],
                        'uom' => $data['uom'], 'company' => $data['company']]);
                return $newID;
            }
        }
    }

    public function submitPickupUnit(Request $request)
    {
        $data = $request->all();

        $data['date'] = Carbon::createFromDate($data['date'])->format('Y-m-d');
        $timestamp = $data['picked_timestamp'];
        $data['picked_timestamp'] = intval(floor($timestamp / 1000));
        $exists = DB::table('pickup_unit')
            ->where('picked_timestamp', '=', $data['picked_timestamp'])
            ->where('idempotency', '=', $data['idempotency'])
            ->get()->toArray();

        if (count($exists) > 0) {
            return true;
        }
        $data['bin'] == null ? $data['bin'] = '' : $data['bin'];
        $data['comment'] == null ? $data['comment'] = '' : $data['comment'];

        DB::table('pickup_unit')->insert($data);

        return true;
    }

    public function getPickupUserNames()
    {
        $result = DB::table('pickup_user_names')->select('userNames')->get()->toArray();

        return $result;

    }

    public function saveUserNames(Request $request)
    {
        $data = $request->input();
        $selectID = DB::table('pickup_user_names')->select('id')->get()->toArray();
        $id = $selectID[0]->id;
        DB::table('pickup_user_names')->where('id', '=', $id)
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
            $result = DB::table('pickup_unit')
                ->where('status', '=', 1)
                ->where('picked_timestamp', '<=', $end)
                ->where('picked_timestamp', '>=', $start)
                ->orderBy('picked_timestamp', 'asc')
                ->get()
                ->map(function ($item) {
                    // Convert and format picked_timestamp using Carbon
                    $item->picked_timestamp = Carbon::createFromTimestamp($item->picked_timestamp)
                        ->setTimezone('America/Los_Angeles')
                        ->format('h:i:s A');
                    return $item;
                })->toArray();
        } else {
            $result = DB::table('pickup_unit')
                ->where('status', '=', 1)
                ->where('user', '=', $user)
                ->where('picked_timestamp', '<=', $end)
                ->where('picked_timestamp', '>=', $start)
                ->orderBy('picked_timestamp', 'asc')
                ->get()
                ->map(function ($item) {
                    // Convert and format picked_timestamp using Carbon
                    $item->picked_timestamp = Carbon::createFromTimestamp($item->picked_timestamp)
                        ->setTimezone('America/Los_Angeles')
                        ->format('h:i:s A');
                    return $item;
                })
                ->toArray();
        }
        return $result;
    }

    public function saveEntriesEdits(Request $request)
    {
        $data = $request->input();
        foreach ($data as $key => $value) {
            if ($value['changes'][0] == 'removeRow') {
                DB::table('pickup_unit')->where('id', '=', $value['id'])
                    ->update(['status' => 0]);
            } else {
                $changes = $value['changes'][0];
                if($changes[1] == 'date'){
                    $changes[3] = Carbon::createFromDate($changes[3])->format('Y-m-d');
                }
                DB::table('pickup_unit')->where('id', '=', $value['id'])
                    ->update([$changes[1] => $changes[3]]);
            }
        }
    }

    public function deleteProduct(Request $request)
    {
        $id = $request->input('id');
        DB::table('pickup_product')->where('id', '=', $id)->delete();
    }

    public function getPickupBin()
    {
        $result = DB::table('pickup_bin')->get()->toArray();

        return $result;
    }

    public function saveBin(Request $request)
    {
        $data = $request->input();
        $exists = DB::table('pickup_bin')->select('id')->where('binNumber', $data['binNumber'])->get()->toArray();
        $id = (array_key_exists('id', $data) ? $data['id'] : -1);
        if (count($exists) > 0 &&  $exists[0]->id != $id) {
            return response()->json(['error' => 'Bin already exists'], 400);
        }
        else{
            if (array_key_exists('id', $data) && $data['id'] > 0) {
                DB::table('pickup_bin')->where('id', '=', $data['id'])
                ->update(['binNumber' => $data['binNumber'], 'yards' => $data['yards'],
                    'company' => $data['company'], 'location' => $data['location']]);
                return 'updated';
            } else {
                $newBin = DB::table('pickup_bin')->insertGetId($data);
                return $newBin;
            }
        }
    }
     public function deleteBin(Request $request)
     {
         $id = $request->input('id');
         DB::table('pickup_bin')->where('id', '=', $id)->delete();
     }

    public function uploadImages(Request $request)
    {
        $request->validate([
            'product' => 'required|string|max:255',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $response = [];
        if ($request->hasFile('images')) {

            foreach ($request->file('images') as $image) {
                // Store each image
                $product = $request->input('product');

                $imagePath = $image->store('img/h96/uploads/'.$product, 'public');
                $name = basename($imagePath);
                $id = DB::table('pickup_images')->insertGetId([
                    'product' => $request->input('product'),
                    'imageName' => $name, // Save path in database
                ]);
                $response[] = [$id,$name];
            }
        }
        return $response;
    }

    public function getImages()
    {
        $result = DB::table('pickup_images')->get()->toArray();

        return $result;
    }

    public function deleteImages(Request $request)
    {
        try {
            $data = $request->input();
            DB::table('pickup_images')->where('id', '=', $data['id'])->delete();
            return true;
        } catch (Exception $e) {
            return response()->json(['error' => 'Delete operation failed', 'details' => $e->getMessage()], 500);
        }
    }
}
