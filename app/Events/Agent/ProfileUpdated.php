<?php
// app/Events/Agent/ProfileUpdated.php
namespace App\Events\Agent;

use App\Models\Agent;
use Illuminate\Foundation\Events\Dispatchable;

class ProfileUpdated
{
    use Dispatchable;
    public function __construct(public Agent $agent) {}
}
