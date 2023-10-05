<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuplicateFundWarning
{
    use Dispatchable, SerializesModels;

    public int $fundId;
    public array $possibleDuplicatesIds;


    public function __construct(int $fundId, array $possibleDuplicatesIds)
    {
        $this->fundId = $fundId;
        $this->possibleDuplicatesIds = $possibleDuplicatesIds;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
