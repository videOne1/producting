<?php

namespace Tests\Unit;

use App\Jobs\ProcessProductImportJob;
use App\Services\ProductImportService;
use Tests\TestCase;

class ProcessProductImportJobTest extends TestCase
{
    public function test_job_processes_import_through_the_service(): void
    {
        $service = $this->createMock(ProductImportService::class);

        $service
            ->expects($this->once())
            ->method('processImportById')
            ->with(123);

        $job = new ProcessProductImportJob(123);

        $job->handle($service);
    }
}
