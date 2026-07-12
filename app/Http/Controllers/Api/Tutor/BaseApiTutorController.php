<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Http\Controllers\Concerns\ResolvesTutor;
use App\Http\Controllers\Controller;

abstract class BaseApiTutorController extends Controller
{
    use ResolvesTutor;
}
