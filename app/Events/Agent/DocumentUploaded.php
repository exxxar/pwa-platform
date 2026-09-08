<?php
// app/Events/Agent/DocumentUploaded.php
namespace App\Events\Agent;

use App\Models\AgentDocument;
use Illuminate\Foundation\Events\Dispatchable;

class DocumentUploaded
{
    use Dispatchable;
    public function __construct(public AgentDocument $document) {}
}
