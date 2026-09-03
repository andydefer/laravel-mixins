<?php

declare(strict_types=1);

namespace AndyDefer\Mixins\Traits;

use AndyDefer\LaravelAddresses\Enums\AddressType;
use AndyDefer\LaravelAddresses\Models\Address;
use AndyDefer\LaravelAddresses\Services\AddressService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Provides Eloquent attributes for address information.
 *
 * @mixin Model
 *
 * @property-read Collection<int, Address> $addresses All addresses for this model
 * @property-read Address|null $primary_address The primary address
 * @property-read Address|null $work_address The work address
 * @property-read bool $has_addresses True if the model has at least one address
 * @property-read int $addresses_count Total number of addresses
 */
trait HasAddressAttributes
{
    /**
     * Get the address service instance.
     */
    private function getAddressService(): AddressService
    {
        return app(AddressService::class);
    }

    /**
     * Get all addresses for this model.
     *
     * @return MorphMany<Address>
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Get the primary address for this model.
     *
     * @return Attribute<Address|null>
     */
    public function primaryAddress(): Attribute
    {
        return Attribute::make(
            get: function (): ?Address {
                /** @var Model $this */
                if (! $this->has_addresses) {
                    return null;
                }

                try {
                    return $this->getAddressService()->primary($this);
                } catch (\Exception) {
                    return null;
                }
            }
        );
    }

    /**
     * Get the work address for this model.
     *
     * @return Attribute<Address|null>
     */
    public function workAddress(): Attribute
    {
        return Attribute::make(
            get: function (): ?Address {
                /** @var Model $this */
                if (! $this->has_addresses) {
                    return null;
                }

                try {
                    $addresses = $this->getAddressService()->all($this);

                    return $addresses->first(fn (Address $address) => $address->address_type === AddressType::WORK);
                } catch (\Exception) {
                    return null;
                }
            }
        );
    }

    /**
     * Determine if this model has any addresses.
     *
     * @return Attribute<bool>
     */
    public function hasAddresses(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                /** @var Model $this */
                try {
                    return $this->getAddressService()->count($this) > 0;
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    /**
     * Get the total number of addresses for this model.
     *
     * @return Attribute<int>
     */
    public function addressesCount(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                /** @var Model $this */
                try {
                    return $this->getAddressService()->count($this);
                } catch (\Exception) {
                    return 0;
                }
            }
        );
    }

    /**
     * Get the addresses collection filtered by type.
     *
     * @param  AddressType  $type  The address type (primary, billing, shipping, work, other)
     * @return Collection<int, Address>
     */
    public function getAddressesByType(AddressType $type): Collection
    {
        /** @var Model $this */
        try {
            $addresses = $this->getAddressService()->all($this);

            return $addresses->filter(fn (Address $address) => $address->address_type === $type);
        } catch (\Exception) {
            return new Collection;
        }
    }

    /**
     * Determine if the model can have addresses.
     *
     * Override this method in your model to add custom conditions.
     *
     * @return bool True if the model can have addresses
     */
    protected function canHaveAddresses(): bool
    {
        return true;
    }

    /**
     * Check if the model has a specific type of address.
     *
     * @param  AddressType  $type  The address type to check
     */
    public function hasAddressType(AddressType $type): bool
    {
        /** @var Model $this */
        try {
            $addresses = $this->getAddressService()->all($this);

            return $addresses->contains(fn (Address $address) => $address->address_type === $type);
        } catch (\Exception) {
            return false;
        }
    }
}
