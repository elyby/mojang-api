<?php
declare(strict_types=1);

namespace Ely\Mojang;

use DateTime;
use Ely\Mojang\Middleware\ResponseConverterMiddleware;
use Ely\Mojang\Middleware\RetryMiddleware;
use Ely\Mojang\Response\QuestionResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

class Api {

    /**
     * @var ClientInterface
     */
    private $client;

    public function setClient(ClientInterface $client): void {
        $this->client = $client;
    }

    /**
     * @return \Ely\Mojang\Response\ApiStatus[]
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#API_Status
     */
    public function apiStatus(): array {
        $response = $this->getClient()->request('GET', 'https://status.mojang.com/check');
        $body = $this->decode($response->getBody()->getContents());

        $result = [];
        foreach ($body as $serviceDeclaration) {
            $serviceName = array_keys($serviceDeclaration)[0];
            $result[$serviceName] = new Response\ApiStatus($serviceName, $serviceDeclaration[$serviceName]);
        }

        return $result;
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
     * @return \Ely\Mojang\Response\NameHistoryItem[]
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#UUID_-.3E_Name_history
     */
    public function uuidToNameHistory(string $uuid): array {
        $response = $this->getClient()->request('GET', "https://api.mojang.com/user/profiles/{$uuid}/names");
        $data = $this->decode($response->getBody()->getContents());

        $result = [];
        foreach ($data as $record) {
            $date = null;
            if (isset($record['changedToAt'])) {
                $date = new DateTime('@' . ($record['changedToAt'] / 1000));
            }

            $result[] = new Response\NameHistoryItem($record['name'], $date);
        }

        return $result;
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
     * @param string[] $names list of users' names
     *
     * @return \Ely\Mojang\Response\ProfileInfo[] response array is indexed with the initial username case
     *
     * @throws \Ely\Mojang\Exception\MojangApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Playernames_-.3E_UUIDs
     */
    public function playernamesToUuids(array $names): array {
        foreach ($names as $i => $name) {
            if (empty($name)) {
                unset($names[$i]);
            }
        }

        if (count($names) > 10) {
            throw new InvalidArgumentException('You cannot request more than 10 names per request');
        }

        $response = $this->getClient()->request('POST', 'https://api.mojang.com/profiles/minecraft', [
            'json' => array_values($names),
        ]);
        $body = $this->decode($response->getBody()->getContents());

        $result = [];
        foreach ($body as $record) {
            $object = Response\ProfileInfo::createFromResponse($record);
            $key = $object->getName();
            foreach ($names as $i => $name) {
                if (mb_strtolower($name) === mb_strtolower($object->getName())) {
                    unset($names[$i]);
                    $key = $name;
                    break;
                }
            }

            $result[$key] = $object;
        }

        return $result;
    }

    /**
     * @param string $accessToken
     * @param string $accountUuid
     * @param string $skinUrl
     * @param bool $isSlim
     *
     * @throws \Ely\Mojang\Exception\MojangApiException
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Change_Skin
     */
    public function changeSkin(string $accessToken, string $accountUuid, string $skinUrl, bool $isSlim): void {
        $this->getClient()->request('POST', "https://api.mojang.com/user/profile/{$accountUuid}/skin", [
            'form_params' => [
                'model' => $isSlim ? 'slim' : '',
                'url' => $skinUrl,
            ],
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
    }

    /**
     * @param string $accessToken
     * @param string $accountUuid
     * @param \Psr\Http\Message\StreamInterface|resource|string $skinContents
     * @param bool $isSlim
     *
     * @throws GuzzleException
     *
     * @deprecated
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
     *
     * @throws \Ely\Mojang\Exception\MojangApiException
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Reset_Skin
     */
    public function resetSkin(string $accessToken, string $accountUuid): void {
        $this->getClient()->request('DELETE', "https://api.mojang.com/user/profile/{$accountUuid}/skin", [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
    }

    /**
     * @return Response\BlockedServersCollection
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Blocked_Servers
     */
    public function blockedServers(): Response\BlockedServersCollection {
        $response = $this->getClient()->request('GET', 'https://sessionserver.mojang.com/blockedservers');
        $hashes = explode("\n", trim($response->getBody()->getContents()));

        return new Response\BlockedServersCollection($hashes);
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
     * @param string $clientToken
     *
     * @return \Ely\Mojang\Response\AuthenticateResponse
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @url https://wiki.vg/Authentication#Refresh
     */
    public function refresh(string $accessToken, string $clientToken): Response\AuthenticateResponse {
        $response = $this->getClient()->request('POST', 'https://authserver.mojang.com/refresh', [
            'json' => [
                'accessToken' => $accessToken,
                'clientToken' => $clientToken,
                'requestUser' => true,
            ],
        ]);
        $body = $this->decode($response->getBody()->getContents());

        return new Response\AuthenticateResponse(
            $body['accessToken'],
            $body['clientToken'],
            [],
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
            $response = $this->getClient()->request('POST', 'https://authserver.mojang.com/validate', [
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
     * @param string $clientToken
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Authentication#Invalidate
     */
    public function invalidate(string $accessToken, string $clientToken): void {
        $this->getClient()->request('POST', 'https://authserver.mojang.com/invalidate', [
            'json' => [
                'accessToken' => $accessToken,
                'clientToken' => $clientToken,
            ],
        ]);
    }

    /**
     * @param string $login
     * @param string $password
     *
     * @return bool
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Authentication#Signout
     */
    public function signout(string $login, string $password): bool {
        try {
            $response = $this->getClient()->request('POST', 'https://authserver.mojang.com/signout', [
                'json' => [
                    'username' => $login,
                    'password' => $password,
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
     * @param string $accessToken
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Check_if_security_questions_are_needed
     */
    public function isSecurityQuestionsNeeded(string $accessToken): void {
        $request = new Request(
            'GET',
            'https://api.mojang.com/user/security/location',
            ['Authorization' => 'Bearer ' . $accessToken]
        );
        $response = $this->getClient()->send($request);
        $rawBody = $response->getBody()->getContents();
        if (!empty($rawBody)) {
            $body = $this->decode($rawBody);
            throw new Exception\OperationException($body['errorMessage'], $request, $response);
        }
    }

    /**
     * @param string $accessToken
     * @return array
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Get_list_of_questions
     */
    public function questions(string $accessToken): array {
        $request = new Request(
            'GET',
            'https://api.mojang.com/user/security/challenges',
            ['Authorization' => 'Bearer ' . $accessToken]
        );
        $response = $this->getClient()->send($request);
        $result = [];
        $body = $this->decode($response->getBody()->getContents());
        foreach ($body as $question) {
            $result[] = new QuestionResponse($question['question']['id'], $question['question']['question'], $question['answer']['id']);
        }

        return $result;
    }

    /**
     * @param string $accessToken
     * @param array $answers
     * @throws GuzzleException
     * @return bool
     *
     * @url https://wiki.vg/Mojang_API#Send_back_the_answers
     */
    public function answer(string $accessToken, array $answers): bool {
        $request = new Request(
            'POST',
            'https://api.mojang.com/user/security/location',
            ['Authorization' => 'Bearer ' . $accessToken],
            json_encode($answers)
        );
        $response = $this->getClient()->send($request);
        $rawBody = $response->getBody()->getContents();

        return empty($rawBody);
    }

    /**
     * @param array $metricKeys
     * @return Response\StatisticsResponse
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Statistics
     */
    public function statistics(array $metricKeys) { // TODO: missing return type annotation
        $response = $this->getClient()->request('POST', 'https://api.mojang.com/orders/statistics', [
            'json' => [
                'metricKeys' => $metricKeys,
            ],
        ]);
        $body = $this->decode($response->getBody()->getContents());

        return new Response\StatisticsResponse($body['total'], $body['last24h'], $body['saleVelocityPerSeconds']);
    }

    /**
     * @param string $accessToken
     * @return \Ely\Mojang\Response\MinecraftServicesProfileResponse
     * @throws GuzzleException
     * @see https://wiki.vg/Mojang_API#Profile_Information
     */
    public function getProfile(string $accessToken): Response\MinecraftServicesProfileResponse {
        $response = $this->getClient()->request('GET', 'https://api.minecraftservices.com/minecraft/profile', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
        $body = $this->decode($response->getBody()->getContents());

        return new Response\MinecraftServicesProfileResponse(
            $body['id'],
            $body['name'],
            array_map(function(array $item) {
                return new Response\MinecraftServicesProfileSkin(
                    $item['id'],
                    $item['state'],
                    $item['url'],
                    $item['variant'],
                    $item['alias'] ?? null
                );
            }, $body['skins']),
            array_map(function(array $item) {
                return new Response\MinecraftServicesProfileCape(
                    $item['id'],
                    $item['state'],
                    $item['url'],
                    $item['alias']
                );
            }, $body['capes'])
        );
    }

    /**
     * @param string $accessToken
     * @param \Psr\Http\Message\StreamInterface|resource|string $skinContents
     * @param bool $isSlim
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Upload_Skin
     */
    public function uploadSkinByFile(string $accessToken, $skinContents, bool $isSlim): void {
        $this->getClient()->request('POST', 'https://api.minecraftservices.com/minecraft/profile/skins', [
            RequestOptions::MULTIPART => [
                [
                    'name' => 'file',
                    'contents' => $skinContents,
                    'filename' => 'char.png',
                ],
                [
                    'name' => 'variant',
                    'contents' => $isSlim ? 'slim' : 'classic',
                ],
            ],
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
    }

    /**
     * @param string $accessToken
     * @param string $skinUrl
     * @param bool $isSlim
     *
     * @throws GuzzleException
     *
     * @url https://wiki.vg/Mojang_API#Change_Skin
     */
    public function uploadSkinByUrl(string $accessToken, string $skinUrl, bool $isSlim): void {
        $this->getClient()->request('POST', 'https://api.minecraftservices.com/minecraft/profile/skins', [
            RequestOptions::JSON => [
                'variant' => $isSlim ? 'slim' : 'classic',
                'url' => $skinUrl,
            ],
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);
    }

    /**
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface {
        if ($this->client === null) {
            $this->client = $this->createDefaultClient();
        }

        return $this->client;
    }

    private function createDefaultClient(): ClientInterface {
        $stack = HandlerStack::create();
        // use after method because middleware executes in reverse order
        $stack->after('http_errors', ResponseConverterMiddleware::create(), 'mojang_response_converter');
        $stack->push(RetryMiddleware::create(), 'retry');

        return new GuzzleClient([
            'handler' => $stack,
            'timeout' => 10,
        ]);
    }

    private function decode(string $response): array {
        return json_decode($response, true);
    }

}
