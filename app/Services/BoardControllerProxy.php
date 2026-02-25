<?php

namespace App\Services;

use App\Http\Controllers\BoardController;
use App\Services\MentionAndNotify;
use App\Services\DraftMessageSender;
use Illuminate\Http\Request;

final class BoardControllerProxy
{
    public function __construct(private readonly BoardController $controller) {}

    public function chatAdd(array $payload)
    {
        $request = new Request($payload);
        return $this->controller->chatAdd($request, new MentionAndNotify());
    }
    public function draftSend(array $payload)
    {
        $request = new Request($payload);
        return $this->controller->draftSend($request, new DraftMessageSender(), new MentionAndNotify());
    }
}
