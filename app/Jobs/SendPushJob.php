<?php

namespace App\Jobs;

use App\Services\Api\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public array $data = []
    ) {
        $this->onQueue('notifications');
    }

    public function handle(PushService $pushService): void
    {
        // $tokens = $pushService->resolveTokensForUser($this->userId); // TODO: implement your token source
        // if (empty($tokens)) {
        //     return;
        // }
        // $pushService->sendToTokens($tokens, $this->title, $this->body, $this->data);
        $pushService->sendToUser($this->userId, $this->title, $this->body, $this->data);

    }
}
