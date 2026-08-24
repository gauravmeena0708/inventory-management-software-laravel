<?php

namespace App\Contracts;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface TabularExporter
{
    /**
     * Download the tabular export instance as a binary response.
     */
    public function download(object $exportInstance, string $fileName): BinaryFileResponse;

    /**
     * Store the tabular export instance on the specified disk.
     */
    public function store(object $exportInstance, string $filePath, string $disk = 'private'): bool;
}
