<?php

namespace App\Handlers\SlackCommand;

use Spatie\SlashCommand\Handlers\BaseHandler;
use Spatie\SlashCommand\Request;
use Spatie\SlashCommand\Response;

abstract class AbstractCommandHandler extends BaseHandler
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * Format an error response
     *
     * @param string $message Error message
     * @return Response
     */
    protected function errorResponse(string $message): Response
    {
        return $this->respondToSlack($message);
    }

    /**
     * Format a success response
     *
     * @param string $message Success message
     * @return Response
     */
    protected function successResponse(string $message): Response
    {
        return $this->respondToSlack($message);
    }
}
