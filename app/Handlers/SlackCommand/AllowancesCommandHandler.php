<?php

namespace App\Handlers\SlackCommand;

use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

class AllowancesCommandHandler extends BaseHandler
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    public function canHandle(Request $request): bool
    {
        return $request->command === 'allowances';
    }

    public function handle(Request $request): Response
    {
        $parts = explode(' ', $request->text);
        $subcommand = strtolower($parts[0] ?? 'help');
        $params = array_slice($parts, 1);

        try {
            $handler = $this->getCommandHandler($subcommand);
            return $handler->handle($request,$params);
        } catch (\Exception $e) {
            return $this->respondToSlack("❌ Error: " . $e->getMessage());
        }
    }

    private function getCommandHandler(string $subcommand): BaseHandler
    {
        return match($subcommand) {
            'login' => new LoginCommandHandler($this->request),
            'categories' => new CategoriesCommandHandler($this->request),
            'map' => new MapCommandHandler($this->request),
            default => new HelpCommandHandler($this->request)
        };
    }
}
