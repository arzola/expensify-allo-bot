<?php

namespace App\Handlers\SlackCommand;

use App\Models\AllowanceCategory;
use App\Models\ExpensifyLogin;

class MapCommandHandler extends AbstractCommandHandler
{
    public function handle(string $request, array $params): array
    {
        if (count($params) < 2) {
            return $this->errorResponse('Invalid format. Use: `/allowances map <expensify_category> <monthly_limit> [description]`');
        }

        $login = ExpensifyLogin::where('slack_user_id', $request)->first();

        if (!$login) {
            return $this->errorResponse('Please set up your Expensify credentials first using `/allowances login <partner_id> <password>`');
        }

        [$expensifyCategory, $monthlyLimit, $description] = array_pad($params, 3, null);

        if (!is_numeric($monthlyLimit)) {
            return $this->errorResponse('Monthly limit must be a number.');
        }

        $availableCategories = $this->expensifyService->getAvailableCategories($login);
        $categoryExists = collect($availableCategories)->contains('name', $expensifyCategory);

        if (!$categoryExists) {
            return $this->errorResponse('Category not found in your Expensify account. Use `/allowances categories` to see available categories.');
        }

        AllowanceCategory::updateOrCreate(
            ['expensify_category' => $expensifyCategory],
            [
                'name' => $expensifyCategory,
                'monthly_limit' => $monthlyLimit,
                'description' => $description,
                'is_active' => true,
            ]
        );

        $response = "Category mapped successfully!\n\n";
        $response .= "*{$expensifyCategory}*\n";
        $response .= "💰 Monthly Limit: \${$monthlyLimit}";

        if ($description) {
            $response .= "\n📝 Description: {$description}";
        }

        return [
            'response_type' => 'ephemeral',
            'text' => $response,
        ];
    }
}
