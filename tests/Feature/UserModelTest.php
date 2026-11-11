<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('hashes the password and hides sensitive attributes', function () {
    $user = User::factory()->create(['password' => 'plain-text']);

    expect(Hash::check('plain-text', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($user->toArray())->not->toHaveKeys(['password', 'remember_token']);
});
