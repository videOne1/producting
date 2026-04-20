<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductImportLine
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $csvRow;
    public $tenantid;
    public $rowNumber;
    public $jobid;
    /**
     * Create a new event instance.
     */
    public function __construct($csvRow, $tenantid, $rowNumber, $jobid)
    {
        $this->csvRow = $csvRow;
        $this->tenantid = $tenantid;
        $this->rowNumber = $rowNumber;
        $this->jobid = $jobid;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
