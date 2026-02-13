<?php

namespace Controllers;

use App\Http\Controllers\EntryController;
use Database\Seeders\testSeeders\pickupBinSeeder;
use Database\Seeders\testSeeders\pickupProductSeeder;
use Database\Seeders\testSeeders\userSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EntriesTest extends TestCase
{
    use RefreshDatabase;

    // protected static bool $setupHasRunOnce = false;

    protected function setUp(): void
    {
        parent::setUp();
        // add testSeed
        $this->seed(pickupBinSeeder::class);
        $this->seed(pickupProductSeeder::class);
        $this->seed(userSeeder::class);

    }

    public function test_ping()
    {
        $controller = new EntryController;
        $this->assertTrue($controller->ping());
    }

    public function test_get_pickup_product()
    {
        $controller = new EntryController;
        $result = $controller->getPickupProduct();
        $this->assertCount(2, $result);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('save_product_data_provider')]
    public function test_save_product($data)
    {

        $input = $data;

        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $controller->saveProduct($request);
        $this->assertDatabaseHas('pickupProduct', ['name' => $input['name'], 'uom' => $input['uom'], 'company' => $input['company']]);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('submit_pickup_unit_data_provider')]
    public function test_submit_pickup_unit($data)
    {
        $input = $data;
        // dd($input);
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->submitPickupUnit($request);
        $this->assertTrue($result);
        $timestamp = intval(floor($input['picked_timestamp'] / 1000));
        $this->assertDatabaseHas('pickupUnit', ['user' => $input['user'],
            'picked_timestamp' => $timestamp, 'idempotency' => $input['idempotency']]);
    }

    public function test_get_pickup_user_names()
    {
        $controller = new EntryController;
        $result = $controller->getPickupUserNames();
        $names = $result[0]->userNames;
        $this->assertTrue(is_string($names));
        $this->assertEquals('Mike,Brent,Johnny,Carlos,Christian,Winston', $names);

    }

    public function test_save_user_names()
    {
        $input = ['Mike,Brent,Johnny,Carlos,Christian,Winston,test'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $controller->saveUserNames($request);
        $this->assertDatabaseHas('pickupUserNames', ['userNames' => $input[0]]);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('get_pickup_unit_range_data_provider')]
    public function test_get_pickup_unit_range($data)
    {
        $fields = ['fields' => $data['fields'][0]];
        $request = Request::createFromBase(Request::create('', 'get', $fields));
        $controller = new EntryController;
        $result = $controller->getPickupUnitRange($request);
        $this->assertTrue(is_array($result));
        $this->assertCount($data['expected'], $result);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('save_entries_edits_data_provider')]
    public function test_save_entries_edits($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $controller->saveEntriesEdits($request);
        foreach ($input as $entry) {
            $changes = $entry['changes'][0];
            $this->assertDatabaseHas('pickupUnit', ['id' => $entry['id'],
                $changes[1] => $changes[3]]);
        }

    }

    public function test_get_pickup_bin()
    {
        $controller = new EntryController;
        $result = $controller->getPickupBin();
        $this->assertTrue(is_array($result));
        $this->assertCount(2, $result);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('save_pickup_bin_data_provider')]
    public function test_save_bin($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $controller->saveBin($request);
        $this->assertDatabaseHas('pickupBin', $input['bin']);
    }

    public static function save_product_data_provider()
    {
        $input[] = [['id' => '1', 'name' => 'test', 'uom' => 'each', 'company' => 'Company A']]; // update
        $input[] = [['id' => '0', 'name' => 'test2', 'uom' => 'each', 'company' => 'Company B']]; // insert

        // dd($input);
        return $input;
    }

    public static function submit_pickup_unit_data_provider()
    {
        $pickupUinits = [
            [['user' => 'userA', 'units' => 1, 'uom' => 'each', 'product' => 'Product A',
                'length' => 0, 'width' => 0, 'height' => 0, 'bin' => '', 'date' => '2026-01-22',
                'picked_timestamp' => 1769117713123, 'company' => 'Company A', 'destination' => 'chip-C',
                'status' => 1, 'idempotency' => '234567891', 'comment' => 'test comment 1']],
            [['user' => 'userA', 'units' => 1, 'uom' => 'each', 'product' => 'Product A',
                'length' => 0, 'width' => 0, 'height' => 0, 'bin' => '', 'date' => '2026-01-22',
                'picked_timestamp' => 1769117091123, 'company' => 'Company A', 'destination' => 'chip-C',
                'status' => 1, 'idempotency' => '876543219', 'comment' => 'test comment 2']],
            [['user' => 'userB', 'units' => 3, 'uom' => 'yards', 'product' => 'Product B',
                'length' => 1, 'width' => 1, 'height' => 1, 'bin' => 'B1', 'date' => '2026-01-22',
                'picked_timestamp' => 17691170863, 'company' => 'Company B', 'destination' => 'chip-C',
                'status' => 1, 'idempotency' => '789123456', 'comment' => '']],
        ];

        return $pickupUinits;
    }

    public static function get_pickup_unit_range_data_provider()
    {
        $data = [[['fields' => ['admin,2026-01-30,2026-02-10'], 'expected' => 3]],
            [['fields' => ['userB,2026-02-09,2026-02-10'], 'expected' => 1]],
            [['fields' => ['userA,2026-02-09,2026-02-10'], 'expected' => 1]]];

        return $data;
    }

    public static function save_entries_edits_data_provider()
    {
        $input = [
            [['input' => [['id' => 1, 'user' => 'userA', 'units' => '1.0000', 'uom' => 'each',
            'product' => 'Pallets', 'length' => '0.00', 'width' => '0.00', 'height' => '0.00',
            'bin' => null, 'date' => '2026-02-10', 'picked_timestamp' => 1770736201, 'company' => 'Company A',
            'destination' => 'Chip - C', 'status' => 1, 'idempotency' => 415732, 'comment' => null,
            'changes' => [[ 0, 'units', '2.0000', '1.0000']]],
            ['id' => 2, 'user' => 'userA', 'units' => '1.0000', 'uom' => 'each',
                'product' => 'Pallets', 'length' => '0.00', 'width' => '0.00', 'height' => '0.00',
                'bin' => null, 'date' => '2026-02-10', 'picked_timestamp' => 1770736201, 'company' => 'Company A',
                'destination' => 'Chip - C', 'status' => 1, 'idempotency' => 415732, 'comment' => null,
                'changes' => [
                    [0, 'units','2.0000', '1.0000']]]],
        ]],
        [['input' => [['id' => 3, 'user' => 'userB', 'units' => '1.0000', 'uom' => 'yards',
            'product' => 'Pallets', 'length' => '2.00', 'width' => '2.00', 'height' => '2.00',
            'bin' => null, 'date' => '2026-02-10', 'picked_timestamp' => 1770736201, 'company' => 'Company A',
            'destination' => 'Chip - C', 'status' => 1, 'idempotency' => 415732, 'comment' => null,
            'changes' => [[ 0, 'units','2.0000', '1.0000']]]]]
        ]];

        return $input;
    }

    public static function save_pickup_bin_data_provider()
    {
        $input = [
            [['input' => ['id' => 0, 'bin' => ['binNumber' => 'B17', 'yards' => 2.5, 'company' => 'company A', 'location' => 'prep']]]],
            [['input' => ['id' => 1, 'bin' => ['binNumber' => 'B1', 'yards' => 1.5, 'company' => 'company A', 'location' => 'VM']]]]
        ];

        return $input;
    }
}
