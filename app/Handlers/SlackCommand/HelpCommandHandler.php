<?php

namespace App\Handlers\SlackCommand;

use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

class HelpCommandHandler extends AbstractCommandHandler
{
    public function handle(Request $request): Response
    {
        $help = "🤖 *Allo Bot Commands*\n\n";
        $help .= "*Login to Expensify*\n";
        $help .= "`/allowances login <partner_id> <password>`\n";
        $help .= "Set up your Expensify credentials\n\n";

        $help .= "*List Categories*\n";
        $help .= "`/allowances categories`\n";
        $help .= "Show available Expensify categories\n\n";

        $help .= "*Check Status*\n";
        $help .= "`/allowances`\n";
        $help .= "Show your current allowance status";

        return $this->respondToSlack($help);
    }

    public function canHandle(Request $request): bool
    {
        return $request->text === 'help';
    }
}
