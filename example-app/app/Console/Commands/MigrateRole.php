<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:migrate-role')]
#[Description('Command description')]
class MigrateRole extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::query()
        ->orderBy('id', 'ASC')
        ->chunk(1000, function($chunk) {
            $now = Carbon::now();
            $dataToInsert = $chunk->map(function($user) use ($now) {
                return [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            });

            Role::query()
            ->insert($dataToInsert->toArray());
            sleep(5);
        });
    }
}
