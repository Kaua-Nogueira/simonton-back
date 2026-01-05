<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController; // Extend BaseController for best practice in L11? No, abstract class Controller usually fine.
// But some Laravel versions require extending BaseController.
// Actually standard Laravel 11 structure: abstract class Controller { // }
// AuthorizesRequests trait is what matters.

abstract class Controller
{
    use AuthorizesRequests;
    //
}
