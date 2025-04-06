<?php

namespace App\Handlers\SlackCommand;

use App\Jobs\ProcessExpensifyCategories;
use App\Jobs\ProcessExpensifyExpenses;
use App\Models\ExpensifyLogin;
use Illuminate\Support\Facades\Log;
use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

class DefaultCommandHandler extends BaseHandler
{
    public function canHandle(Request $request): bool
    {
        return $request->text === '';
    }

    public function handle(Request $request): Response
    {

        $login = ExpensifyLogin::where('slack_user_id', $request->userId)->first();

        if (!$login) {
            return $this->respondToSlack("🙅‍♂️ You need to connect your Expensify account first. Use `/allowances login` to do so.");
        }

        ProcessExpensifyExpenses::dispatch($login, $request);

        return $this->respondToSlack("🤓 Crunching your Expensify stuff... This might take a few moments.");
    }
}
