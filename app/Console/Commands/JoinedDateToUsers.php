<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Infrastructure\Kintone\KintoneClient;

class JoinedDateToUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:joined-date-to-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(KintoneClient $kintoneClient)
    {
        $users = User::where('retire', 0)->where('partner_flag', 0)->get();
        $user_codes = $users->pluck('user_code')->toArray();
        $user_codes_str = implode('","', $user_codes);
        $fields = ['入社日', '社員コード'];
        $query = "社員コード in (\"{$user_codes_str}\")";

        $records = $kintoneClient->getRecords(7, $query, $fields);

        foreach ($records as $record) {
            $userCode = $record['社員コード']['value'] ?? null;
            $joinedDate = $record['入社日']['value'] ?? null;

            if (blank($userCode)) {
                continue;
            }

            if (blank($joinedDate)) {
                $joinedDate = null;
            }

            User::where('user_code', $userCode)
                ->update(['joined_date' => $joinedDate]);
        }
    }
}
