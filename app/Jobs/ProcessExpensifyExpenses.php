<?php

namespace App\Jobs;

use App\Models\AllowanceCategory;
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

        // 1. Fetch Allowances from DB
        $allowances = AllowanceCategory::all();

        if ($allowances->isEmpty()) {
            $this->respondToSlack("🤔 No allowance categories found in the database.")->send();
            return;
        }

        // 2. Fetch Spent Amounts from Expensify
        $spentAmounts = $expensifyService->getSpentAmountsByCategories($this->login);

        // 3. Calculate Balances and Format Message
        $messageLines = ["📊 Here are your current allowance balances:"];
        $totalRemaining = 0;
        $totalAllowance = 0;

        foreach ($allowances as $allowance) {
            $categoryName = $allowance->name;
            $icon = $allowance->description;
            $allowanceAmount = (float) $allowance->annual_limit * 100; // Convert to cents
            $spentAmount = (float) ($spentAmounts[$categoryName] ?? 0);
            $remainingAmount = $allowanceAmount - $spentAmount;

            $totalAllowance += $allowanceAmount;
            $totalRemaining += $remainingAmount;

            $formattedAllowance = number_format($allowanceAmount / 100, 2);
            $formattedSpent = number_format($spentAmount / 100, 2);
            $formattedRemaining = number_format($remainingAmount / 100, 2);

            $messageLines[] = "• {$icon}*{$categoryName}:* Used *\$ {$formattedSpent}* / *\$ {$formattedAllowance}*, 💵 *\$ {$formattedRemaining}* left.";
        }

        // Add totals
        $formattedTotalAllowance = number_format($totalAllowance / 100, 2);
        $formattedTotalRemaining = number_format($totalRemaining / 100, 2);
        $messageLines[] = "\nTotal Balance Remaining: *\${$formattedTotalRemaining}* / \${$formattedTotalAllowance}";

        $message = implode("\n", $messageLines);

        // 4. Send Response to Slack
        $this->respondToSlack($message)->send();
    }
}
