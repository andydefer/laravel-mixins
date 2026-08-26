<?php

declare(strict_types=1);

namespace AndyDefer\Mixins\Tests\Fixtures\Enums;

enum TestUserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
