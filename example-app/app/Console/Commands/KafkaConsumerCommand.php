<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class KafkaConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:kafka-consumer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume Debezium events from Kafka and modify data before inserting it into replica';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conf = new Conf();

        // Set the group id. This can be any unique string for your consumer group
        $conf->set('group.id', 'laravel-replica-group');

        // Set the bootstrap servers
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));

        // Set where to start consuming if there's no committed offset for the group
        $conf->set('auto.offset.reset', 'earliest');

        $consumer = new KafkaConsumer($conf);

        // Subscribe to our topic
        $consumer->subscribe(['dbserver1.app_primary.users']);

        $this->info('Started Kafka Consumer listening on dbserver1.app_primary.users...');

        while (true) {
            $message = $consumer->consume(120 * 1000); // Wait for 120 seconds

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $this->processMessage($message->payload);
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    // No more messages in this partition, just wait
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    $this->info('Timed out waiting for messages...');
                    break;
                default:
                    $this->error('Error occurred in Kafka consumer: ' . $message->errstr());
                    break;
            }
        }
    }

    private function processMessage($payload)
    {
        $data = json_decode($payload, true);

        if (!$data || !isset($data['payload'])) {
            return;
        }

        $debeziumPayload = $data['payload'];
        $op = $debeziumPayload['op'] ?? ''; // c = create, u = update, d = delete

        // We care about create and update
        if ($op === 'c' || $op === 'u') {
            $after = $debeziumPayload['after'];

            // --- MODIFY DATA HERE ---
            // Example modification: Add a prefix to the name
            $after['name'] = '[MODIFIED BY LARAVEL] ' . $after['name'];

            // Sync to replica database
            $this->syncToReplica($after);
        } elseif ($op === 'd') {
            $before = $debeziumPayload['before'];
            $this->deleteFromReplica($before['id']);
        }
    }

    private function syncToReplica($userData)
    {
        $this->info("Syncing user ID: " . $userData['id']);

        // Filter valid table columns
        $validColumns = ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'];
        $dataToSync = array_intersect_key($userData, array_flip($validColumns));

        // Use upsert or manual checking
        DB::connection('mysql_replica')->table('users')->updateOrInsert(
            ['id' => $dataToSync['id']],
            $dataToSync
        );

        $this->info("Successfully synced user ID: " . $userData['id'] . " with modified name.");
    }

    private function deleteFromReplica($userId)
    {
        $this->info("Deleting user ID: " . $userId);

        DB::connection('mysql_replica')->table('users')->where('id', $userId)->delete();

        $this->info("Successfully deleted user ID: " . $userId);
    }
}
