<?php

namespace App\Listeners;

use App\Events\ProductImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessProductImportJob
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ProductImport $event): void
    {
        dd($event->filePath);
    }
}
