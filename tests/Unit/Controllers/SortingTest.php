<?php

namespace Controllers;

use App\Http\Controllers\SortingController;
use Database\Seeders\testSeeders\pickupBinSeeder;
use Database\Seeders\testSeeders\pickupSortingProductSeeder;
use Database\Seeders\testSeeders\userSeeder;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SortingTest extends TestCase
{
    use RefreshDatabase;

    // protected static bool $setupHasRunOnce = false;

    protected function setUp(): void
    {
        parent::setUp();
        // add testSeed
        $this->seed(pickupBinSeeder::class);
        $this->seed(pickupSortingProductSeeder::class);
        $this->seed(userSeeder::class);

    }

    public function test_get_sorting_product()
    {
        $controller = new SortingController;
        $result = $controller->getPickupSortingProduct();
        $this->assertCount(2, $result);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('save_sorting_product_data_provider')]
    public function test_save_sorting_product($data)
    {

        $input = $data;

        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new SortingController;
        $controller->saveSortingProduct($request);
        $this->assertDatabaseHas('pickupsortingproduct', ['name' => $input['name']]);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('submit_sorting_product_data_provider')]
    public function test_submit_sorting_product($data)
    {
        $input = $data;
        // dd($input);
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new SortingController;
        $result = $controller->submitPickupSortingProduct($request);
        $timestamp = intval(floor($input['picked_timestamp'] / 1000));
        $this->assertDatabaseHas('pickupsorting', ['user' => $input['user'],
            'picked_timestamp' => $timestamp, 'idempotency' => $input['idempotency']]);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('get_sorting_product_range_data_provider')]
    public function test_get_sorting_product_range($data)
    {
        $fields = ['fields' => $data['fields'][0]];
        $request = Request::createFromBase(Request::create('', 'get', $fields));
        $controller = new SortingController;
        $result = $controller->getSortingProductRange($request);
        $this->assertTrue(is_array($result));
        $this->assertCount($data['expected'], $result);
    }

    /**
     * @throws Exception
     * */
    #[DataProvider('save_sorting_edits_data_provider')]
    public function test_save_sorting_edits($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new SortingController;
        $controller->saveSortingEdits($request);
        foreach ($input as $entry) {
            $changes = $entry['changes'][0];
            $this->assertDatabaseHas('pickupsorting', ['id' => $entry['id'],
                $changes[1] => $changes[3]]);
        }

    }

    public static function save_sorting_product_data_provider()
    {
        $input[] = [['id' => '1', 'name' => 'test']]; // update
        $input[] = [['id' => '0', 'name' => 'test2']]; // insert

        // dd($input);
        return $input;
    }

    public static function submit_sorting_product_data_provider()
    {
        $sortingProducts = [
            [['user' => 'userA', 'units' => 1,  'product' => 'Product test',
                'date' => '2026-01-22', 'picked_timestamp' => 1769117713123, 'company' => 'Company A',
                'status' => 1, 'idempotency' => '234567891']],
            [['user' => 'userA', 'units' => 1,  'product' => 'Product testA',
                'date' => '2026-01-22', 'picked_timestamp' => 1769117091123, 'company' => 'Company A',
                'status' => 1, 'idempotency' => '876543219']],
            [['user' => 'userB', 'units' => 3,  'product' => 'Product testB',
                'date' => '2026-01-22', 'picked_timestamp' => 17691170863, 'company' => 'Company B',
                'status' => 1, 'idempotency' => '789123456']],
        ];

        return $sortingProducts;
    }

    public static function get_sorting_product_range_data_provider()
    {
        $data = [[['fields' => ['admin,2026-01-30,2026-02-10'], 'expected' => 3]],
            [['fields' => ['userB,2026-02-09,2026-02-10'], 'expected' => 1]],
            [['fields' => ['userA,2026-02-09,2026-02-10'], 'expected' => 1]]];

        return $data;
    }

    public static function save_sorting_edits_data_provider()
    {
        $input = [
            [['input' => [['id' => 1, 'user' => 'userA', 'units' => '1.0000', 'uom' => 'each',
                'product' => 'Pallets', 'length' => '0.00', 'width' => '0.00', 'height' => '0.00',
                'bin' => null, 'date' => '2026-02-10', 'picked_timestamp' => 1770736201, 'company' => 'Company A',
                'destination' => 'Chip - C', 'status' => 1, 'idempotency' => 415732, 'comment' => null,
                'changes' => [[0, 'units', '2.0000', '1.0000']]],
                ['id' => 2, 'user' => 'userA', 'units' => '1.0000', 'uom' => 'each',
                    'product' => 'Pallets', 'length' => '0.00', 'width' => '0.00', 'height' => '0.00',
                    'bin' => null, 'date' => '2026-02-10', 'picked_timestamp' => 1770736201, 'company' => 'Company A',
                    'destination' => 'Chip - C', 'status' => 1, 'idempotency' => 415732, 'comment' => null,
                    'changes' => [
                        [0, 'units', '2.0000', '1.0000']]]],
            ]],
            [['input' => [['id' => 3, 'user' => 'userB', 'units' => '1.0000', 'uom' => 'yards',
                'product' => 'Pallets', 'length' => '2.00', 'width' => '2.00', 'height' => '2.00',
                'bin' => null, 'date' => '2026-02-10', 'picked_timestamp' => 1770736201, 'company' => 'Company A',
                'destination' => 'Chip - C', 'status' => 1, 'idempotency' => 415732, 'comment' => null,
                'changes' => [[0, 'units', '2.0000', '1.0000']]]]],
            ]];

        return $input;
    }
}
