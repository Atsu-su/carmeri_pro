<?php

namespace App\Models;

use App\Traits\FilterByUserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    use FilterByUserStatus;
    use HasFactory;
}
