<?php

declare(strict_types=1);

use Streeboga\LaravelApi\Contracts\Enums\HasColor;
use Streeboga\LaravelApi\Contracts\Enums\HasIcon;
use Streeboga\LaravelApi\Contracts\Enums\HasLabel;
use Streeboga\LaravelApi\Tests\Fixtures\Enums\TestBookStatus;

it('enum implements all three contracts', function () {
    $draft = TestBookStatus::Draft;

    expect($draft)->toBeInstanceOf(HasLabel::class)
        ->and($draft)->toBeInstanceOf(HasColor::class)
        ->and($draft)->toBeInstanceOf(HasIcon::class);
});

it('returns string for label/color/icon', function () {
    foreach (TestBookStatus::cases() as $case) {
        expect($case->label())->toBeString()
            ->and($case->color())->toBeString()
            ->and($case->icon())->toBeString();
    }
});

it('exposes distinct values per case', function () {
    expect(TestBookStatus::Draft->label())->toBe('Черновик')
        ->and(TestBookStatus::Published->color())->toBe('green');
});
