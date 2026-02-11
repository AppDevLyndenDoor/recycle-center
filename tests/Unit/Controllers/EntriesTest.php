<?php

namespace Controllers;

use App\Http\Controllers\EntryController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Http\Request;

class EntriesTest extends TestCase
{
    use RefreshDatabase;

    protected static bool $setupHasRunOnce = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (! static::$setupHasRunOnce) {
            $this->seed(EntryController::class);
            static::$setupHasRunOnce = true;
        }
    }

    public function test_ping()
    {
        $controller = new EntryController;
        $this->assertTrue($controller->ping());
    }

    public function test_get_pickup_product($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->getPickupProduct();
        foreach ($result as $product) {
            $this->assertTrue(is_string($product->name));
        }
    }

    public function test_save_product($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->saveProduct($request);
        $this->assertDatabaseHas('pickupproduct', ['name' => 'test']);
    }

    public function test_submit_pickup_unit($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->submitPickupUnit($request);
        $this->assertTrue($result);
        $this->assertDatabaseHas('pickupunit', ['name' => 'test']);
    }

    public function test_get_pickup_user_names($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->getPickupUserNames();
        foreach ($result as $user) {
            $this->assertTrue(is_string($user->name));
        }
    }

    public function test_save_user_names($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->saveUserNames($request);
        $this->assertDatabaseHas('pickupuser', ['name' => 'test']);
    }

    public function test_get_pickup_unit_range($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->getPickupUnitRange($request);
        $this->assertTrue(is_array($result));
    }

    public function test_save_entries_edits($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $controller->saveEntriesEdits($request);
        $this->assertDatabaseHas('pickupunit', ['name' => 'test']);
    }

    public function test_get_pickup_bin($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $result = $controller->getPickupBin();
        $this->assertTrue(is_array($result));
    }

    public function test_save_bin($data)
    {
        $input = $data['input'];
        $request = Request::createFromBase(Request::create('', 'get', $input));
        $controller = new EntryController;
        $controller->saveEntriesEdits($request);
        $this->assertDatabaseHas('pickupbin', ['name' => 'test']);
    }
}
