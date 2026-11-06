<?php

declare(strict_types=1);

namespace App\Services\Chatbot\Contracts;

/**
 * Standard contract for Chatbot AI tools and ReAct function calling.
 */
interface ChatbotToolInterface
{
    /**
     * Unique function name recognized by the LLM function calling dispatcher.
     */
    public function getName(): string;

    /**
     * Comprehensive description of the tool for the LLM system prompt.
     */
    public function getDescription(): string;

    /**
     * JSON Schema definition of input parameters for LLM tool registration.
     *
     * @return array<string, mixed>
     */
    public function getParametersSchema(): array;

    /**
     * Safe execution logic querying isolated Eloquent/PostGIS models.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function execute(array $arguments): array;
}
