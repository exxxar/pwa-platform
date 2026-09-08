<?php
// app/Events/Agent/PayoutRequested.php
namespace App\Events\Agent;

use App\Models\Payout;
use Illuminate\Foundation\Events\Dispatchable;

class PayoutRequested
{
    use Dispatchable;
    public function __construct(public Payout $payout) {}
}
