<?php

declare(strict_types=1);

use Syriable\Reportable\Tests\TestCase;
use Syriable\Reportable\Tests\TestSupport\Models\Post;
use Syriable\Reportable\Tests\TestSupport\Models\User;

uses(TestCase::class)->in(__DIR__);

function createUser(string $name = 'Reporter'): User
{
    return User::query()->create(['name' => $name]);
}

function createPost(string $title = 'A post'): Post
{
    return Post::query()->create(['title' => $title]);
}
