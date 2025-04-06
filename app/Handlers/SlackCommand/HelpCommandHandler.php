<?php

namespace App\Handlers\SlackCommand;

class HelpCommandHandler extends AbstractCommandHandler
{
    public function handle(string $request, array $params): array
    {
        $help = "🤖 *Allo Bot Commands*\n\n";
        $help .= "*Login to Expensify*\n";
        $help .= "`/allowances login <partner_id> <password>`\n";
        $help .= "Set up your Expensify credentials\n\n";

        $help .= "*List Categories*\n";
        $help .= "`/allowances categories`\n";
        $help .= "Show available Expensify categories\n\n";

        $help .= "*Map Category*\n";
        $help .= "`/allowances map <expensify_category> <monthly_limit> [description]`\n";
        $help .= "Map an Expensify category to an allowance\n\n";

        $help .= "*Check Status*\n";
        $help .= "`/allowances`\n";
        $help .= "Show your current allowance status";

        return [
            'response_type' => 'ephemeral',
            'text' => $help,
        ];
    }
}
