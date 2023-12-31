<?php

namespace ChatGPTPHP;

use Exception;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class ChatGPT
{
	private $baseUrl = 'https://api.openai.com/';

	private $model = 'gpt-3.5-turbo';

	private $modelEmbbeding = 'text-embedding-ada-002';

	private $key;

	private $temperature = 1;

	private $topP = 1;

	private $messages = [];

	private $http;

	public function __construct(
		string $key,
		string $baseUrl = null,
		string $model = null,
		int $temperature = null,
		int $topP = null,
		int $timeout = 360
	) {
		$this->key = 'Bearer '.$key;
		if ($baseUrl) {
			$this->baseUrl = $baseUrl;
		}
		if ($model) {
			$this->model = $model;
		}
		if ($temperature) {
			$this->temperature = $temperature;
		}
		if ($topP) {
			$this->topP = $topP;
		}

		$this->http = new Client([
			'base_uri' => $this->baseUrl,
			'timeout' => $timeout,
			'stream' => true,
		]);
	}

	/**
	 * Description:
	 * Adds a message to the conversation.
	 * @param  string  $message
	 * @param  string  $role
	 *
	 * @return void
	 */
	public function addMessage(string $message, string $role = 'user'): void
	{
		$this->messages[] = [
			'role' => $role,
			'content' => $message,
		];
	}

	/**
	 * Description:
	 * Asks a question or sends a prompt to the model.
	 * @param  string  $prompt
	 * @param  string|null  $user
	 * @param  bool  $stream
	 *
	 * @return Generator
	 * @throws Exception|GuzzleException
	 */
	public function ask(string $prompt, string $user = null, bool $stream = false): Generator
	{
		// Description:
		$this->addMessage($prompt);

		$data = [
			'model' => $this->model,
			'messages' => $this->messages,
			'stream' => $stream,
			'temperature' => $this->temperature,
			'top_p' => $this->topP,
			'n' => 1,
			'user' => $user ?? 'chatgpt-php',
		];

		try {
			$response = $this->http->post(
				'v1/chat/completions',
				[
					'json' => $data,
					'headers' => [
						'Authorization' => $this->key,
					],
					'stream' => $stream,
				]
			);
		} catch (RequestException $e) {
			if ($e->hasResponse()) {
				throw new Exception(Psr7\Message::toString($e->getResponse()));
			} else {
				throw new Exception($e->getMessage());
			}
		}

		$answer = '';

		// use stream
		if ($stream) {
			$data = $response->getBody();
			while (! $data->eof()) {
				$raw = Psr7\Utils::readLine($data);
				$line = self::formatStreamMessage($raw);
				if (self::checkStreamFields($line)) {
					$answer = $line['choices'][0]['delta']['content'];
					$messageId = $line['id'];

					yield [
						"answer" => $answer,
						"id" => $messageId,
						"model" => $this->model,
					];
				}
				unset($raw, $line);
			}
			$this->addMessage($answer, 'assistant');
		} else {
			$data = json_decode($response->getBody()->getContents(), true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('Response is not json');
			}

			if (! $this->checkFields($data)) {
				throw new Exception('Field missing');
			}

			$answer = $data['choices'][0]['message']['content'];
			$this->addMessage($answer, 'assistant');

			yield [
				'answer' => $answer,
				'id' => $data['id'],
				'model' => $this->model,
				'usage' => $data['usage'],
			];
		}
	}

	/**
	 * Description:
	 * Checks if the required fields are present in the response.
	 * @param  mixed  $line
	 *
	 * @return bool
	 */
	public function checkFields($line): bool
	{
		return isset($line['choices'][0]['message']['content']) && isset($line['id']) && isset($line['usage']);
	}

	/**
	 * Description: 
	 * Checks if the required fields are present in the streamed response.
	 * @param  mixed  $line
	 *
	 * @return bool
	 */
	public function checkStreamFields($line): bool
	{
		return isset($line['choices'][0]['delta']['content']) && isset($line['id']);
	}

	/**
	 * Description: 
	 * Formats a streamed message to extract the relevant data.
	 * @param  string  $line
	 *
	 * @return mixed
	 */
	public function formatStreamMessage(string $line)
	{
		preg_match('/data: (.*)/', $line, $matches);
		if (empty($matches[1])) {
			return false;
		}

		$line = $matches[1];
		$data = json_decode($line, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}

		return $data;
	}

	public function createEmbbeding(string $message)
	{

		$data = array(
			"input" => $message,
			"model" => $this->modelEmbbeding
		);

		try {
			$response = $this->http->post(
				'v1/embeddings',
				[
					'json' => $data,
					'headers' => [
						'Authorization' => $this->key,
					]
				]
			);
		} catch (RequestException $e) {
			if ($e->hasResponse()) {
				throw new Exception(Psr7\Message::toString($e->getResponse()));
			} else {
				throw new Exception($e->getMessage());
			}
		}

		$data = json_decode($response->getBody()->getContents(), true);
		return $data;
	}
}
