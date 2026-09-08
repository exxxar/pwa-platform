<?php
// app/DTOs/Agent/UploadDocumentDTO.php
namespace App\DTOs\Agent;

use Illuminate\Http\UploadedFile;

class UploadDocumentDTO
{
    public function __construct(
        public readonly int $document_type_id,
        public readonly UploadedFile $file,
    ) {}
}
