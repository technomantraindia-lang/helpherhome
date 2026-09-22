<?php

namespace App\Services;

class GlobalSearchService
{
    public function __construct(private readonly ReportService $reports) {}

    public function search(string $term): array
    {
        return $this->reports->globalSearch($term);
    }
}
