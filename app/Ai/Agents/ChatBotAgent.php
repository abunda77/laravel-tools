<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class ChatBotAgent implements Agent, Conversational
{
    use Promptable;

    /**
     * @param  Message[]  $messages
     */
    public function __construct(
        private readonly array $messages = [],
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
You are a careful workspace chatbot. Answer in the user's language.
When source context is provided, use it and cite it inline with bracket numbers like [1].
If a claim needs current web evidence but no source context is provided, say that web sources are not available for that claim.
When documents or images are attached, analyze only what is present in the attachments and clearly separate attachment observations from web-sourced claims.
PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return $this->messages;
    }
}
