<?php

namespace App\Kafka\Handlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\Handler;
use Junges\Kafka\Contracts\MessageConsumer;

class UserSyncHandler implements Handler
{
    /**
     * @param  \Junges\Kafka\Contracts\ConsumerMessage $message
     * @param  \Junges\Kafka\Contracts\MessageConsumer $consumer
     */
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $body = $message->getBody();
        
        // Debezium message typically has a 'payload' key if schemas are enabled
        $payload = isset($body['payload']) ? $body['payload'] : $body;

        if (!$payload || !isset($payload['op'])) {
            return;
        }

        $op = $payload['op']; // c=create, u=update, d=delete

        if ($op === 'c' || $op === 'u') {
            $after = $payload['after'];

            // Sync to roles table
            if (isset($after['role'])) {
                $this->syncToReplica($after);
            }
        } elseif ($op === 'd') {
            $before = $payload['before'];
            $this->deleteFromReplica($before['id']);
        }
    }

    private function syncToReplica(array $userData)
    {
        $dataToSync = [
            'user_id' => $userData['id'],
            'name' => $userData['role'],
            'updated_at' => now(),
        ];

        DB::table('roles')->updateOrInsert(
            ['user_id' => $dataToSync['user_id']],
            array_merge($dataToSync, [
                'created_at' => now(),
                'updated_at' => now()
            ])
        );
    }

    private function deleteFromReplica($userId)
    {
        DB::table('roles')->where('user_id', $userId)->delete();
    }
}
