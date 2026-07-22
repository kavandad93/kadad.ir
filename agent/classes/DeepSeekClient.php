<?php
// classes/DeepSeekClient.php

class DeepSeekClient {
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private float $temperature;
    private int $maxTokens;

    public function __construct(array $config) {
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://api.deepseek.com/v1';
        $this->model = $config['default_model'] ?? 'deepseek-chat';
        $this->temperature = (float)($config['temperature'] ?? 0.2);
        $this->maxTokens = (int)($config['max_tokens'] ?? 4096);
    }

    public function getSystemInstructions(): string {
        return "You are an advanced, high-performance Full-Stack AI Coding Agent called Kadad AI Agent.\n" .
               "You execute system changes directly through deterministic JSON object operations.\n" .
               "You must analyze context, execute files modifications, and stop using the 'finish' command when done.\n\n" .
               "CRITICAL: You MUST answer using ONLY a single valid JSON object containing exactly two root-level fields: 'explanation' and 'action'. Do not output any markdown code blocks encapsulation (no ```json code blocks). Plain text raw JSON formatting string structure rules output only.\n\n" .
               "Structure syntax payload model:\n" .
               "{\n" .
               "  \"explanation\": \"Text explanation targeting user on step intent\",\n" .
               "  \"action\": {\n" .
               "     \"type\": \"write_file\" | \"replace_text\" | \"append_file\" | \"create_file\" | \"delete_file\" | \"create_folder\" | \"delete_folder\" | \"rename\" | \"read_file\" | \"search\" | \"finish\",\n" .
               "     \"path\": \"relative/path/to/target.ext\",\n" .
               "     \"content\": \"Full text content needed for write/create operations or text block mappings\",\n" .
               "     \"search_text\": \"text string search segments inside file for replace_text or global search execution query\",\n" .
               "     \"replace_text\": \"replacement text details inside code module modifications\",\n" .
               "     \"new_path\": \"relative/path/target_new.ext\"\n" .
               "  }\n" .
               "}";
    }

    public function sendPayload(array $messages, bool $stream = false) {
        $url = rtrim($this->baseUrl, '/') . '/chat/completions';
        
        $headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey
        ];

        $postData = [
            "model" => $this->model,
            "messages" => $messages,
            "temperature" => $this->temperature,
            "max_tokens" => $this->maxTokens,
            "stream" => $stream
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, !$stream);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        if ($stream) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                echo $data;
                if (ob_get_level() > 0) ob_flush();
                flush();
                return strlen($data);
            });
            curl_exec($ch);
            curl_close($ch);
            exit;
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception("cURL Transfer Error Connection Failed: " . $err);
        }

        return json_decode($response, true);
    }
}
