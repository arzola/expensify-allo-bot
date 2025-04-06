<?php

namespace App\Handlers\SlackCommand;

use App\Models\ExpensifyLogin;
use App\Services\ExpensifyService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

class LoginCommandHandler extends AbstractCommandHandler
{
    public function handle(Request $request): Response
    {
        $params = explode(' ', $request->text);
        $expensifyService = app(ExpensifyService::class);

        if (count($params) < 3) {
            return $this->errorResponse('Invalid format. Use: `/allowances login <partner_id> <password>`');
        }

        [$cmd, $partnerId, $password] = $params;

        // Validate partner ID format (allows letters, numbers, underscores, and dots)
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $partnerId)) {
            return $this->errorResponse('Invalid Partner ID format. It should only contain letters, numbers, underscores, and dots.');
        }

        // Check if user is already logged in
        if (ExpensifyLogin::isLoggedIn($request->userId)) {
            return $this->errorResponse('You are already logged in. Use `/allowances logout` to remove your current credentials.');
        }

        if (!$expensifyService->validateCredentials($partnerId, $password)) {
            return $this->errorResponse('Invalid Expensify credentials. Please check your Partner ID and password.');
        }

        try {
            ExpensifyLogin::updateOrCreate(
                ['slack_user_id' => $request->userId],
                [
                    'partner_id' => $partnerId,
                    'password' => $password,
                ]
            );

            return $this->successResponse('Expensify credentials saved successfully! You can now use other commands.');
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('An error occurred while saving your credentials. Please try again later.');
        }
    }

    public function canHandle(Request $request): bool
    {
        return Str::startsWith($request->text, 'login');
    }
}
