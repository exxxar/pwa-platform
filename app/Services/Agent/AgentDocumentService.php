<?php

namespace App\Services\Agent;

use App\Models\Agent;
use App\Models\AgentDocument;
use App\Events\Agent\DocumentUploaded;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AgentDocumentService
{
    /**
     * Загрузить документ
     */
    public function uploadDocument(Agent $agent, int $documentTypeId, UploadedFile $file): AgentDocument
    {
        // Валидация типа файла
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowed)) {
            throw new \App\Exceptions\Agent\DocumentValidationException(
                'Допустимые форматы: ' . implode(', ', $allowed)
            );
        }

        // Максимальный размер 10 МБ
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \App\Exceptions\Agent\DocumentValidationException(
                'Максимальный размер файла: 10 МБ'
            );
        }

        // Сохраняем файл
        $path = $file->store("agents/{$agent->id}/documents", 'public');

        // Находим или создаём запись о документе
        $document = AgentDocument::updateOrCreate(
            ['agent_id' => $agent->id, 'type' => $this->mapTypeIdToType($documentTypeId)],
            [
                'title'               => $this->getDocumentTitle($documentTypeId),
                'file_path'           => $path,
                'file_name'           => $file->getClientOriginalName(),
                'file_size'           => $file->getSize(),
                'is_uploaded'         => true,
                'uploaded_at'         => now(),
                'verification_status' => 'pending',
            ]
        );

        // Пересчитываем статус верификации
        $this->recalculateVerificationStatus($agent);

        event(new DocumentUploaded($document));

        return $document;
    }

    /**
     * Пересчитать общий статус верификации
     */
    public function recalculateVerificationStatus(Agent $agent): void
    {
        $required = $agent->documents()->where('is_required', true)->count();
        $uploaded = $agent->documents()->where('is_required', true)->where('is_uploaded', true)->count();

        if ($required === 0) {
            $status = 'not_started';
        } elseif ($uploaded === $required) {
            $status = 'verified';
        } elseif ($uploaded > 0) {
            $status = 'partial';
        } else {
            $status = 'not_started';
        }

        $agent->update(['verification_status' => $status]);
    }

    /**
     * Получить список документов агента с прогрессом
     */
    public function getDocumentsWithProgress(Agent $agent): array
    {
        $documents = $agent->documents()->get();
        $required = $documents->where('is_required', true);

        return [
            'documents' => $documents,
            'progress'  => [
                'uploaded' => $required->where('is_uploaded', true)->count(),
                'required' => $required->count(),
                'percent'  => $required->count() > 0
                    ? round(($required->where('is_uploaded', true)->count() / $required->count()) * 100)
                    : 0,
            ],
            'status' => $agent->verification_status,
        ];
    }

    private function mapTypeIdToType(int $id): string
    {
        return match ($id) {
            1 => 'registration_cert',
            2 => 'ogrn_cert',
            3 => 'bank_card',
            default => 'other',
        };
    }

    private function getDocumentTitle(int $id): string
    {
        return match ($id) {
            1 => 'Справка о постановке на учет (КНД 1122035)',
            2 => 'Свидетельство ОГРНИП / ОГРН',
            3 => 'Карточка предприятия',
            default => 'Документ',
        };
    }
}
