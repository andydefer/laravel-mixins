<?php

declare(strict_types=1);

// tests/Integration/Traits/HasAddressAttributesTest.php

namespace AndyDefer\Mixins\Tests\Integration\Traits;

use AndyDefer\LaravelAddresses\Enums\AddressType;
use AndyDefer\LaravelAddresses\Records\AddressRecord;
use AndyDefer\LaravelAddresses\Services\AddressService;
use AndyDefer\Mixins\Tests\Fixtures\Models\TestModel;
use AndyDefer\Mixins\Tests\IntegrationTestCase;
use AndyDefer\PhpVo\Enums\Country;
use AndyDefer\PhpVo\ValueObjects\PostalCodeVO;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class HasAddressAttributesTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private AddressService $addressService;

    private TestModel $addressable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addressService = app(AddressService::class);

        $this->addressable = TestModel::create([
            'slug' => 'test-addressable',
            'coordinates' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
            'nullable_coordinates' => null,
            'custom_coordinates' => [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
            ],
        ]);
    }

    // ============================================================
    // TESTS: addresses relationship
    // ============================================================

    public function test_addresses_relationship_returns_correct_addresses(): void
    {
        // Arrange
        $record1 = AddressRecord::from([
            'street' => '123 Test Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::PRIMARY,
        ]);

        $record2 = AddressRecord::from([
            'street' => '456 Work Street',
            'city' => 'Lyon',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('69001'),
            'address_type' => AddressType::WORK,
        ]);

        $this->addressService->add($this->addressable, $record1);
        $this->addressService->add($this->addressable, $record2);

        // Act
        $addresses = $this->addressable->addresses;

        // Assert
        $this->assertCount(2, $addresses);
        $this->assertEquals('123 Test Street', $addresses[0]->street);
        $this->assertEquals('456 Work Street', $addresses[1]->street);
    }

    public function test_addresses_relationship_returns_empty_collection_when_no_addresses(): void
    {
        // Act
        $addresses = $this->addressable->addresses;

        // Assert
        $this->assertCount(0, $addresses);
        $this->assertInstanceOf(Collection::class, $addresses);
    }

    // ============================================================
    // TESTS: primaryAddress
    // ============================================================

    public function test_primary_address_returns_correct_primary_address(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '123 Primary Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::PRIMARY,
        ]);

        $address = $this->addressService->add($this->addressable, $record);
        $this->addressService->setPrimary($this->addressable, $address->id);

        // Act
        $primary = $this->addressable->primary_address;

        // Assert
        $this->assertNotNull($primary);
        $this->assertEquals('123 Primary Street', $primary->street);
        $this->assertEquals(AddressType::PRIMARY, $primary->address_type);
    }

    public function test_primary_address_returns_null_when_no_primary_address(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '123 Test Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::OTHER,
        ]);

        $this->addressService->add($this->addressable, $record);

        // Act
        $primary = $this->addressable->primary_address;

        // Assert
        $this->assertNull($primary);
    }

    public function test_primary_address_returns_null_when_no_addresses(): void
    {
        // Act
        $primary = $this->addressable->primary_address;

        // Assert
        $this->assertNull($primary);
    }

    // ============================================================
    // TESTS: workAddress
    // ============================================================

    public function test_work_address_returns_correct_work_address(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '456 Work Street',
            'city' => 'Lyon',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('69001'),
            'address_type' => AddressType::WORK,
        ]);

        $this->addressService->add($this->addressable, $record);

        // Act
        $work = $this->addressable->work_address;
        // Assert
        $this->assertNotNull($work);
        $this->assertEquals('456 Work Street', $work->street);
        $this->assertEquals(AddressType::WORK, $work->address_type);
    }

    public function test_work_address_returns_null_when_no_work_address(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '123 Test Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::OTHER,
        ]);

        $this->addressService->add($this->addressable, $record);

        // Act
        $work = $this->addressable->work_address;

        // Assert
        $this->assertNull($work);
    }

    public function test_work_address_returns_null_when_no_addresses(): void
    {
        // Act
        $work = $this->addressable->work_address;

        // Assert
        $this->assertNull($work);
    }

    // ============================================================
    // TESTS: hasAddresses
    // ============================================================

    public function test_has_addresses_returns_true_when_addresses_exist(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '123 Test Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::OTHER,
        ]);

        $this->addressService->add($this->addressable, $record);

        // Act
        $hasAddresses = $this->addressable->has_addresses;

        // Assert
        $this->assertTrue($hasAddresses);
    }

    public function test_has_addresses_returns_false_when_no_addresses(): void
    {
        // Act
        $hasAddresses = $this->addressable->has_addresses;

        // Assert
        $this->assertFalse($hasAddresses);
    }

    // ============================================================
    // TESTS: addressesCount
    // ============================================================

    public function test_addresses_count_returns_correct_number(): void
    {
        // Arrange
        $record1 = AddressRecord::from([
            'street' => '123 Test Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::OTHER,
        ]);

        $record2 = AddressRecord::from([
            'street' => '456 Work Street',
            'city' => 'Lyon',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('69001'),
            'address_type' => AddressType::WORK,
        ]);

        $this->addressService->add($this->addressable, $record1);
        $this->addressService->add($this->addressable, $record2);

        // Act
        $count = $this->addressable->addresses_count;

        // Assert
        $this->assertEquals(2, $count);
    }

    public function test_addresses_count_returns_zero_when_no_addresses(): void
    {
        // Act
        $count = $this->addressable->addresses_count;

        // Assert
        $this->assertEquals(0, $count);
    }

    // ============================================================
    // TESTS: getAddressesByType
    // ============================================================

    public function test_get_addresses_by_type_returns_correct_addresses(): void
    {
        // Arrange
        $record1 = AddressRecord::from([
            'street' => '123 Primary Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::PRIMARY,
        ]);

        $record2 = AddressRecord::from([
            'street' => '456 Work Street',
            'city' => 'Lyon',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('69001'),
            'address_type' => AddressType::WORK,
        ]);

        $record3 = AddressRecord::from([
            'street' => '789 Billing Street',
            'city' => 'Marseille',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('13001'),
            'address_type' => AddressType::BILLING,
        ]);

        $this->addressService->add($this->addressable, $record1);
        $this->addressService->add($this->addressable, $record2);
        $this->addressService->add($this->addressable, $record3);

        // Act
        $primaryAddresses = $this->addressable->getAddressesByType(AddressType::PRIMARY);
        $workAddresses = $this->addressable->getAddressesByType(AddressType::WORK);
        $billingAddresses = $this->addressable->getAddressesByType(AddressType::BILLING);

        // Assert
        $this->assertCount(1, $primaryAddresses);
        $this->assertEquals('123 Primary Street', $primaryAddresses->first()->street);

        $this->assertCount(1, $workAddresses);
        $this->assertEquals('456 Work Street', $workAddresses->first()->street);

        $this->assertCount(1, $billingAddresses);
        $this->assertEquals('789 Billing Street', $billingAddresses->first()->street);
    }

    public function test_get_addresses_by_type_returns_empty_collection_when_no_addresses(): void
    {
        // Act
        $addresses = $this->addressable->getAddressesByType(AddressType::PRIMARY);

        // Assert
        $this->assertCount(0, $addresses);
        $this->assertInstanceOf(Collection::class, $addresses);
    }

    public function test_get_addresses_by_type_returns_empty_collection_when_type_not_found(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '123 Test Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::OTHER,
        ]);

        $this->addressService->add($this->addressable, $record);

        // Act
        $addresses = $this->addressable->getAddressesByType(AddressType::PRIMARY);

        // Assert
        $this->assertCount(0, $addresses);
    }

    // ============================================================
    // TESTS: hasAddressType
    // ============================================================

    public function test_has_address_type_returns_true_when_type_exists(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '123 Work Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::WORK,
        ]);

        $this->addressService->add($this->addressable, $record);

        // Act
        $hasWork = $this->addressable->hasAddressType(AddressType::WORK);

        // Assert
        $this->assertTrue($hasWork);
    }

    public function test_has_address_type_returns_false_when_type_does_not_exist(): void
    {
        // Arrange
        $record = AddressRecord::from([
            'street' => '123 Work Street',
            'city' => 'Paris',
            'country' => Country::FR,
            'postal_code' => PostalCodeVO::from('75001'),
            'address_type' => AddressType::WORK,
        ]);

        $this->addressService->add($this->addressable, $record);

        // Act
        $hasPrimary = $this->addressable->hasAddressType(AddressType::PRIMARY);

        // Assert
        $this->assertFalse($hasPrimary);
    }

    public function test_has_address_type_returns_false_when_no_addresses(): void
    {
        // Act
        $hasAddress = $this->addressable->hasAddressType(AddressType::WORK);

        // Assert
        $this->assertFalse($hasAddress);
    }
}
