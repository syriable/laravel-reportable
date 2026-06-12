<?php

declare(strict_types=1);

namespace Syriable\Reportable\Tests\TestSupport\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Syriable\Reportable\Concerns\CanReport;
use Syriable\Reportable\Concerns\HasReports;

class User extends Authenticatable
{
    use CanReport;
    use HasReports;

    protected $table = 'users';

    protected $guarded = [];
}
