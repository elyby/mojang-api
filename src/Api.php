<?php
declare(strict_types=1);

namespace Ely\Mojang;

use Ely\Mojang\Middleware\ResponseConverterMiddleware;
use Ely\Mojang\Middleware\RetryMiddleware;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Ramsey\Uuid\Uuid;

class Api {

    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client) {
        $this->client = $client;
    }

    /**
     * @param callable $handler HTTP handler function to use with the stack. If no
     *                          handler is provided, the best handler for your
     *                          system will be utilized.
     *
     * @return static
     */
    public static function create(callable $handler = null): self {
        $stack = HandlerStack::create($handler);
        // use after method because middleware executes in reverse order
        $stack->after('http_errors', ResponseConverterMiddleware::create(), 'mojang_response_converter');
        $stack->push(RetryMiddleware::create(), 'retry');

        return new static(new GuzzleClient([
            'handler' => $stack,
            'timeout' => 10,
        ]));
    }

    /**
     * @param string $username
     * @param int    $atTime
     *
     * @return \Ely\Mojang\Response\ProfileInfo
     *
     * @throws \Ely\Mojang\Exception\MojangApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @url http://wiki.vg/Mojang_API#Username_-.3E_UUID_at_time
     */
    public function usernameToUUID(string $username, int $atTime = null): Response\ProfileInfo {
        $query = [];
        if ($atTime !== null) {
            $query['atTime'] = $atTime;
        }

        $response = $this->getClient()->request('GET', "https://api.mojang.com/users/profiles/minecraft/{$username}", [
            'query' => $query,
        ]);

        $data = $this->decode($response->getBody()->getContents());

        return Response\ProfileInfo::createFromResponse($data);
    }

    /**
     * @param string $uuid
     *
     * @return \Ely\Mojang\Response\ProfileResponse
     *
     * @throws \Ely\Mojang\Exception\MojangApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @url http://wiki.vg/Mojang_API#UUID_-.3E_Profile_.2B_Skin.2FCape
     */
    public function uuidToTextures(string $uuid): Response\ProfileResponse {
        $response = $this->getClient()->request('GET', "https://sessionserver.mojang.com/session/minecraft/profile/{$uuid}", [
            'query' => [
                'unsigned' => false,
            ],
        ]);
        $body = $this->decode($response->getBody()->getContents());

        return new Response\ProfileResponse($body['id'], $body['name'], $body['properties']);
    }

    /**
     * Helper method to exchange username to the corresponding textures.
     *
     * @param string $username
     *
     * @return \Ely\Mojang\Response\ProfileResponse
     *
     * @throws GuzzleException
     * @throws \Ely\Mojang\Exception\MojangApiException
     */
    public function usernameToTextures(string $username): Response\ProfileResponse {
        return $this->uuidToTextures($this->usernameToUUID($username)->getId());
    }

    /**
     * @param string $login
     * @param string $password
     * @param string $clientToken
     *
     * @return \Ely\Mojang\Response\AuthenticateResponse
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @url https://wiki.vg/Authentication#Authenticate
     */
    public function authenticate(
        string $login,
        string $password,
        string $clientToken = null
    ): Response\AuthenticateResponse {
        if ($clientToken === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $clientToken = Uuid::uuid4()->toString();
        }

        $response = $this->getClient()->request('POST', 'https://authserver.mojang.com/authenticate', [
            'json' => [
                'username' => $login,
                'password' => $password,
                'clientToken' => $clientToken,
                'requestUser' => true,
                'agent' => [
                    'name' => 'Minecraft',
                    'version' => 1,
                ],
            ],
        ]);
        $body = $this->decode($response->getBody()->getContents());

        return new Response\AuthenticateResponse(
            $body['accessToken'],
            $body['clientToken'],
            $body['availableProfiles'],
            $body['selectedProfile'],
            $body['user']
        );
    }

    /**
     * @param string $accessToken
     *
     * @return bool
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @url https://wiki.vg/Authentication#Validate
     */
    public function validate(string $accessToken): bool {
        try {
            $response = $this->getClient()->request('POST', 'https://authserver.mojang.com/authenticate', [
                'json' => [
                    'accessToken' => $accessToken,
                ],
            ]);
            if ($response->getStatusCode() === 204) {
                return true;
            }
        } catch (Exception\ForbiddenException $e) {
            // Suppress exception and let it just exit below
        }

        return false;
    }

    /**
     * @param string $accessToken
     * @param string $accountUuid
     * @param \Psr\Http\Message\StreamInterface|resource|string $skinContents
     * @param bool $isSlim
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Upload_Skin
     */
    public function uploadSkin(string $accessToken, string $accountUuid, $skinContents, bool $isSlim): void {
        $this->getClient()->request('PUT', "https://api.mojang.com/user/profile/{$accountUuid}/skin", [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => $skinContents,
                    'filename' => 'char.png',
                ],
                [
                    'name' => 'model',
                    'contents' => $isSlim ? 'slim' : '',
                ],
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
    }

    /**
     * @param string $accessToken
     * @param string $accountUuid
     * @param string $serverId
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Protocol_Encryption#Client
     */
    public function joinServer(string $accessToken, string $accountUuid, string $serverId): void {
        $this->getClient()->request('POST', 'https://sessionserver.mojang.com/session/minecraft/join', [
            'json' => [
                'accessToken' => $accessToken,
                'selectedProfile' => $accountUuid,
                'serverId' => $serverId,
            ],
        ]);
    }

    /**
     * @param string $username
     * @param string $serverId
     *
     * @return \Ely\Mojang\Response\ProfileResponse
     *
     * @throws \Ely\Mojang\Exception\NoContentException
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Protocol_Encryption#Server
     */
    public function hasJoinedServer(string $username, string $serverId): Response\ProfileResponse {
        $uri = (new Uri('https://sessionserver.mojang.com/session/minecraft/hasJoined'))
            ->withQuery(http_build_query([
                'username' => $username,
                'serverId' => $serverId,
            ], '', '&', PHP_QUERY_RFC3986));
        $request = new Request('GET', $uri);
        $response = $this->getClient()->send($request);
        $rawBody = $response->getBody()->getContents();
        if (empty($rawBody)) {
            throw new Exception\NoContentException($request, $response);
        }

        $body = $this->decode($rawBody);

        return new Response\ProfileResponse($body['id'], $body['name'], $body['properties']);
    }

    /**
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface {
        return $this->client;
    }

    private function decode(string $response): array {
        return json_decode($response, true);
    }

}
