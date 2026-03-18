<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Kafka\Handlers\UserSyncHandler;
use Junges\Kafka\Facades\Kafka;

#[Signature('app:sync-role-command')]
#[Description('Command description')]
class SyncRoleCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $consumer = Kafka::consumer(["dbserver1.app_primary.users"])
            ->withBrokers(config('kafka.brokers', 'kafka:9092'))
            ->withAutoCommit()
            ->withHandler(new UserSyncHandler)
            ->build();
            
        $consumer->consume();
    }
}
