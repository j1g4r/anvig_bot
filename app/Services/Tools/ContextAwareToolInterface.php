<?php

namespace App\Services\Tools;

use App\Models\Conversation;

interface ContextAwareToolInterface extends ToolInterface
{
    /**
     * Set the conversation context for the tool.
     *
     * @param Conversation $conversation
     * @return void
     */
    public function setConversation(Conversation $conversation): void;
}
