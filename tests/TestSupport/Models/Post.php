<?php

declare(strict_types=1);

namespace Syriable\Reportable\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Syriable\Reportable\Concerns\HasReports;

class Post extends Model
{
    use HasReports;

    protected $table = 'posts';

    protected $guarded = [];
}
