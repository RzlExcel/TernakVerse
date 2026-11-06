<?php

declare(strict_types=1);

namespace App\Services\Chatbot\Contracts;

use Generator;

/**
 * Contract for multi-engine Large Language Model (LLM) drivers
 * supporting synchronous chat completions, streaming tokens, and ReAct function calling.
 */
interface LlmDriverInterface
{
    /**
     * Generate complete chat response and handle tool calls synchronously.
     *
     * @param array<int, array<string, mixed>> $messages Chat history messages (role, content, tool_calls, etc.)
     * @param array<int, array<string, mixed>> $tools JSON Schema definitions of available function tools
     * @param array<string, mixed> $options Provider-specific options (model, temperature, timeout, etc.)
     * @return array{
     *     content: ?string,
     *     tool_calls: array<int, array<string, mixed>>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     finish_reason: ?string
     * }
     */
    public function generateChat(array $messages, array $tools = [], array $options = []): array;

    /**
     * Stream progressive tokens or tool call events via a PHP Generator.
     *
     * @param array<int, array<string, mixed>> $messages Chat history messages
     * @param array<int, array<string, mixed>> $tools JSON Schema definitions of available function tools
     * @param array<string, mixed> $options Provider-specific options
     * @return Generator<int, array{
     *     content?: ?string,
     *     tool_calls?: array<int, array<string, mixed>>,
     *     finish_reason?: ?string
     * }, void, void>
     */
    public function streamChat(array $messages, array $tools = [], array $options = []): Generator;

    /**
     * Get the active LLM provider name (e.g. 'gemini', 'openai', 'ollama', 'simulation').
     */
    public function getProviderName(): string;
}
