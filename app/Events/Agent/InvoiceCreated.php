<?php
// app/Events/Agent/InvoiceCreated.php
namespace App\Events\Agent;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

class InvoiceCreated
{
    use Dispatchable;
    public function __construct(public Invoice $invoice) {}
}
