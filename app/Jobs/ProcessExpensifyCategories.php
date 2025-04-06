<?php

namespace App\Jobs;

use App\Models\ExpensifyLogin;
use App\Services\ExpensifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\SlashCommand\Jobs\SlashCommandResponseJob;
use Spatie\SlashCommand\Request;

class ProcessExpensifyCategories extends SlashCommandResponseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ExpensifyLogin $login;

    public function __construct(ExpensifyLogin $login, Request $request)
    {
        $this->login = $login;
        $this->request = $request;
    }

    public function handle()
    {
        $expensifyService = app(ExpensifyService::class);

        $categories = $expensifyService->getAvailableCategories($this->login);

        if (empty($categories)) {
            $this->respondToSlack("No categories found for your Expensify account.")->send();
            return;
        }

        $message = "Here are your available categories:\n";
        foreach ($categories as $category) {
            $message .= "• {$category}\n";
        }

        $this->respondToSlack($message)->send();
    }
}
