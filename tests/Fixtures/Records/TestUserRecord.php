<?php

declare(strict_types=1);

namespace AndyDefer\Mixins\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\LaravelCluster\ValueObjects\ClusterVO;
use AndyDefer\Mixins\Tests\Fixtures\Collections\TestLanguageCollection;
use AndyDefer\Mixins\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\Mixins\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\Mixins\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\Mixins\Tests\Fixtures\ValueObjects\TestSlug;

final class TestUserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?TestUserStatus $status = null,
        public readonly ?TestUserRole $role = null,
        public readonly ?TestUserGrade $grade = null,
        public readonly ?TestSlug $slug = null,
        public readonly ?TestLanguageCollection $languages = null,
        public readonly ?ClusterVO $metadata = null,
    ) {}
}
