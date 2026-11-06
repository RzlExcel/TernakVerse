<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider and Model
    |--------------------------------------------------------------------------
    |
    | Primary LLM driver and chat model used for generative responses.
    | Supported providers: 'gemini', 'openai', 'ollama'.
    |
    */
    'default_provider' => env('CHATBOT_DEFAULT_PROVIDER', 'gemini'),
    'default_model' => env('CHATBOT_DEFAULT_MODEL', 'gemini-1.5-flash'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Supported embedding providers: 'gemini', 'openai', 'local_hash'.
    | Default embedding dimension: 1536 (OpenAI text-embedding-3-small,
    | or local_hash deterministic vector).
    |
    */
    'embedding_provider' => env('CHATBOT_EMBEDDING_PROVIDER', 'gemini'),
    'embedding_dimension' => (int) env('CHATBOT_EMBEDDING_DIMENSION', 1536),

    /*
    |--------------------------------------------------------------------------
    | API Keys & External Gateway Endpoints
    |--------------------------------------------------------------------------
    */
    'gemini_api_key' => env('GEMINI_API_KEY'),
    'openai_api_key' => env('OPENAI_API_KEY'),
    'ollama_base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),

    /*
    |--------------------------------------------------------------------------
    | Context Window & ReAct Loop Limits
    |--------------------------------------------------------------------------
    */
    'max_context_tokens' => 4096,
    'max_tool_iterations' => 4,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Max requests per decay window for public and authenticated users.
    |
    */
    'rate_limits' => [
        'public' => 20,
        'auth' => 60,
        'decay_minutes' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Veterinary Safety & Medical Disclaimer
    |--------------------------------------------------------------------------
    |
    | Mandatory clinical triage disclaimer appended to veterinary health advice.
    |
    */
    'medical_disclaimer' => 'Peringatan: Analisis ini merupakan sistem pendukung keputusan awal dan tidak menggantikan pemeriksaan langsung oleh Medik/Paramedik Veteriner berwenang. Segera hubungi Poskeswan setempat untuk tindakan medis.',

    /*
    |--------------------------------------------------------------------------
    | Poskeswan Banyumas Official Directory
    |--------------------------------------------------------------------------
    |
    | Verified regional emergency animal healthcare post contacts in Banyumas.
    |
    */
    'poskeswan_contacts' => [
        'purwokerto' => [
            'nama' => 'Poskeswan Purwokerto',
            'telepon' => '0812-3456-7890',
            'wilayah' => 'Purwokerto Utara, Selatan, Barat, Timur',
        ],
        'sokaraja' => [
            'nama' => 'Poskeswan Sokaraja',
            'telepon' => '0812-3456-7891',
            'wilayah' => 'Sokaraja, Kalibagor, Banyumas, Patikraja',
        ],
        'ajibarang' => [
            'nama' => 'Poskeswan Ajibarang',
            'telepon' => '0812-3456-7892',
            'wilayah' => 'Ajibarang, Pekuncen, Cilongok, Gumelar',
        ],
        'wangon' => [
            'nama' => 'Poskeswan Wangon',
            'telepon' => '0812-3456-7893',
            'wilayah' => 'Wangon, Jatilawang, Rawalo, Purwojati, Lumbir',
        ],
        'tambak' => [
            'nama' => 'Poskeswan Tambak',
            'telepon' => '0812-3456-7894',
            'wilayah' => 'Tambak, Sumpiuh, Kemranjen',
        ],
    ],

];
