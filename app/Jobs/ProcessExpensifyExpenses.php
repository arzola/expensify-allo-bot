<?php

namespace App\Jobs;

use App\Models\ExpensifyLogin;
use App\Services\ExpensifyService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\SlashCommand\Jobs\SlashCommandResponseJob;
use Spatie\SlashCommand\Request;

class ProcessExpensifyExpenses extends SlashCommandResponseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ExpensifyLogin $login;

    public function __construct(ExpensifyLogin $login, Request $request)
    {
        $this->login = $login;
        $this->request = $request;
    }

    /**
     * @throws GuzzleException
     */
    public function handle(): void
    {
        $expensifyService = app(ExpensifyService::class);

        $expenses = $expensifyService->getSpentAmountsByCategories($this->login);

        if (empty($expenses)) {
            $this->respondToSlack("No expenses found for your Expensify account. 🤯")->send();
            return;
        }

        $message = "💰 Here are your expenses by category:\n";

        $this->respondToSlack($message)->send();
    }
}
