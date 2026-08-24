<?php

namespace App\Services\Importer\TableImporters;

use App\Models\FileRecord;
use App\Models\Task;

class TaskTableImporter extends BaseTableImporter
{
    /**
     * Execute the task import routine.
     *
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return array<string, int>
     */
    public function import(?callable $progressCallback = null): array
    {
        $this->importTasks($progressCallback);

        return $this->counts;
    }

    /**
     * Import legacy tasks.
     */
    protected function importTasks(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('tasks')) {
            return;
        }

        $query = $this->legacyQuery('tasks')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $title = trim($rowArray['name'] ?? ($rowArray['title'] ?? ''));
                if (blank($title)) {
                    $title = "Task #{$legacyId}";
                }

                $fileId = $rowArray['file_id'] ?? null;
                if ($fileId !== null) {
                    $fileExists = FileRecord::on($this->targetConnection)
                        ->where('id', $fileId)
                        ->orWhere('legacy_id', $fileId)
                        ->exists();

                    if (!$fileExists) {
                        $this->recordAnomaly(
                            'tasks',
                            'missing_foreign_key',
                            "Task #{$legacyId} references non-existent file ID {$fileId}.",
                            ['task_id' => $legacyId, 'file_id' => $fileId]
                        );
                        $fileId = null;
                    }
                }

                $priority = $this->mapPriority($rowArray['priority'] ?? null);
                $status = $this->mapStatus($rowArray['status'] ?? null);
                $remarks = $rowArray['remark'] ?? ($rowArray['remarks'] ?? null);

                $attributes = [
                    'title' => $title,
                    'description' => $rowArray['description'] ?? $remarks,
                    'file_id' => $fileId,
                    'priority' => $priority,
                    'status' => $status,
                    'due_date' => $rowArray['due_date'] ?? null,
                    'remarks' => $remarks,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Task::on($this->targetConnection)->updateOrCreate(
                    ['id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('tasks');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('tasks', $processed, $total);
            }
        });
    }

    /**
     * Map numeric or string priority to standard string representation.
     */
    protected function mapPriority(mixed $priority): string
    {
        if ($priority === null || $priority === '') {
            return 'normal';
        }

        $str = strtolower(trim((string) $priority));

        return match ($str) {
            '1', 'low' => 'low',
            '2', 'normal', 'medium' => 'normal',
            '3', 'high' => 'high',
            '4', 'urgent', 'critical' => 'urgent',
            default => 'normal',
        };
    }

    /**
     * Map status to standard string representation.
     */
    protected function mapStatus(mixed $status): string
    {
        if ($status === null || $status === '') {
            return 'pending';
        }

        $str = strtolower(trim((string) $status));

        return match ($str) {
            'completed', 'closed', 'done' => 'completed',
            'in_progress', 'active', 'running' => 'in_progress',
            default => 'pending',
        };
    }
}
