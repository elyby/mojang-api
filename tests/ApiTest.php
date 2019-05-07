<?php
declare(strict_types=1);

namespace Ely\Mojang\Test;

use Ely\Mojang\Api;
use Ely\Mojang\Exception\ForbiddenException;
use Ely\Mojang\Exception\NoContentException;
use Ely\Mojang\Middleware\ResponseConverterMiddleware;
use Ely\Mojang\Middleware\RetryMiddleware;
use Ely\Mojang\Response\ApiStatus;
use Ely\Mojang\Response\NameHistoryItem;
use Ely\Mojang\Response\ProfileInfo;
use Ely\Mojang\Response\Properties\TexturesProperty;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\parse_query;

class ApiTest extends TestCase {

    /**
     * @var Api
     */
    private $api;

    /**
     * @var \GuzzleHttp\Handler\MockHandler
     */
    private $mockHandler;

    /**
     * @var \Psr\Http\Message\RequestInterface[]
     */
    private $history;

    protected function setUp(): void {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->history = [];
        $handlerStack->push(Middleware::history($this->history), 'history');
        $handlerStack->after('http_errors', ResponseConverterMiddleware::create(), 'mojang_responses');
        $handlerStack->push(RetryMiddleware::create(), 'retry');
        $client = new Client(['handler' => $handlerStack]);
        $this->api = new Api();
        $this->api->setClient($client);
    }

    public function testApiStatus() {
        $this->mockHandler->append($this->createResponse(200, [
            [
                'minecraft.net' => 'yellow',
            ],
            [
                'session.minecraft.net' => 'green',
            ],
            [
                'textures.minecraft.net' => 'red',
            ],
        ]));

        $result = $this->api->apiStatus();

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://status.mojang.com/check', (string)$request->getUri());

        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(ApiStatus::class, $result);
        $this->assertArrayHasKey('minecraft.net', $result);
        $this->assertSame('minecraft.net', $result['minecraft.net']->getServiceName());
        $this->assertSame('yellow', $result['minecraft.net']->getStatus());

        $this->assertArrayHasKey('session.minecraft.net', $result);
        $this->assertSame('session.minecraft.net', $result['session.minecraft.net']->getServiceName());
        $this->assertSame('green', $result['session.minecraft.net']->getStatus());

        $this->assertArrayHasKey('textures.minecraft.net', $result);
        $this->assertSame('textures.minecraft.net', $result['textures.minecraft.net']->getServiceName());
        $this->assertSame('red', $result['textures.minecraft.net']->getStatus());
    }

    public function testUsernameToUuid() {
        $this->mockHandler->append($this->createResponse(200, [
            'id' => '86f6e3695b764412a29820cac1d4d0d6',
            'name' => 'MockUsername',
            'legacy' => false,
        ]));

        $result = $this->api->usernameToUUID('MockUsername');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.mojang.com/users/profiles/minecraft/MockUsername', (string)$request->getUri());

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getId());
        $this->assertSame('MockUsername', $result->getName());
        $this->assertFalse($result->isLegacy());
        $this->assertFalse($result->isDemo());
    }

    public function testUuidToNameHistory() {
        $this->mockHandler->append($this->createResponse(200, [
            [
                'name' => 'Gold',
            ],
            [
                'name' => 'Diamond',
                'changedToAt' => 1414059749000,
            ],
        ]));

        $result = $this->api->uuidToNameHistory('86f6e3695b764412a29820cac1d4d0d6');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.mojang.com/user/profiles/86f6e3695b764412a29820cac1d4d0d6/names', (string)$request->getUri());

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(NameHistoryItem::class, $result);

        $this->assertSame('Gold', $result[0]->getName());
        $this->assertNull($result[0]->getChangedToAt());

        $this->assertSame('Diamond', $result[1]->getName());
        $this->assertSame('2014-10-23T10:22:29+00:00', $result[1]->getChangedToAt()->format(DATE_ATOM));
    }

    public function testUsernameToUuidWithAtParam() {
        $this->mockHandler->append($this->createResponse(200, [
            'id' => '86f6e3695b764412a29820cac1d4d0d6',
            'name' => 'MockUsername',
            'legacy' => false,
        ]));

        $this->api->usernameToUUID('MockUsername', 1553961511);

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame(
            'https://api.mojang.com/users/profiles/minecraft/MockUsername?atTime=1553961511',
            (string)$request->getUri()
        );
    }

    public function testUuidToTextures() {
        $this->mockHandler->append($this->createResponse(200, [
            'id' => '86f6e3695b764412a29820cac1d4d0d6',
            'name' => 'MockUsername',
            'properties' => [
                [
                    'name' => 'textures',
                    'value' => base64_encode(json_encode([
                        'timestamp' => 1553961848860,
                        'profileId' => '86f6e3695b764412a29820cac1d4d0d6',
                        'profileName' => 'MockUsername',
                        'signatureRequired' => true,
                        'textures' => [
                            'SKIN' => [
                                'url' => 'http://textures.minecraft.net/texture/292009a4925b58f02c77dadc3ecef07ea4c7472f64e0fdc32ce5522489362680',
                            ],
                            'CAPE' => [
                                'url' => 'http://textures.minecraft.net/texture/capePath',
                            ],
                        ],
                    ])),
                    'signature' => 'mocked signature value',
                ],
            ],
        ]));

        $result = $this->api->uuidToTextures('86f6e3695b764412a29820cac1d4d0d6');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame(
            'https://sessionserver.mojang.com/session/minecraft/profile/86f6e3695b764412a29820cac1d4d0d6?unsigned=0',
            (string)$request->getUri()
        );

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getId());
        $this->assertSame('MockUsername', $result->getName());
        $props = $result->getProps();
        /** @var TexturesProperty $texturesProperty */
        $texturesProperty = $props[0];
        $this->assertInstanceOf(TexturesProperty::class, $texturesProperty);
        $this->assertSame('textures', $texturesProperty->getName());
        $this->assertSame('mocked signature value', $texturesProperty->getSignature());
        $textures = $texturesProperty->getTextures();
        $this->assertSame(1553961848, $textures->getTimestamp());
        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $textures->getProfileId());
        $this->assertSame('MockUsername', $textures->getProfileName());
        $this->assertTrue($textures->isSignatureRequired());
        $this->assertNotNull($textures->getSkin());
        $this->assertSame(
            'http://textures.minecraft.net/texture/292009a4925b58f02c77dadc3ecef07ea4c7472f64e0fdc32ce5522489362680',
            $textures->getSkin()->getUrl()
        );
        $this->assertFalse($textures->getSkin()->isSlim());
        $this->assertSame('http://textures.minecraft.net/texture/capePath', $textures->getCape()->getUrl());
    }

    public function testPlayernamesToUuids() {
        $this->mockHandler->append($this->createResponse(200, [
            [
                'id' => '86f6e3695b764412a29820cac1d4d0d6',
                'name' => 'MockUsername',
                'legacy' => false,
                'demo' => true,
            ],
        ]));

        $result = $this->api->playernamesToUuids([null, '', 'mockusername', 'nonexists']);

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.mojang.com/profiles/minecraft', (string)$request->getUri());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $body = $request->getBody()->getContents();
        $this->assertJsonStringEqualsJsonString('["mockusername", "nonexists"]', $body);

        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(ProfileInfo::class, $result);
        $this->assertArrayHasKey('mockusername', $result);
        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result['mockusername']->getId());
        $this->assertSame('MockUsername', $result['mockusername']->getName());
        $this->assertFalse($result['mockusername']->isLegacy());
        $this->assertTrue($result['mockusername']->isDemo());
    }

    public function testUsernameToTextures() {
        $this->mockHandler->append($this->createResponse(200, [
            'id' => '86f6e3695b764412a29820cac1d4d0d6',
            'name' => 'MockUsername',
            'legacy' => false,
        ]));
        $this->mockHandler->append($this->createResponse(200, [
            'id' => '86f6e3695b764412a29820cac1d4d0d6',
            'name' => 'MockUsername',
            'properties' => [],
        ]));

        $this->api->usernameToTextures('MockUsername');
        /** @var \Psr\Http\Message\RequestInterface $request1 */
        /** @var \Psr\Http\Message\RequestInterface $request2 */
        [0 => ['request' => $request1], 1 => ['request' => $request2]] = $this->history;
        $this->assertSame('https://api.mojang.com/users/profiles/minecraft/MockUsername', (string)$request1->getUri());
        $this->assertStringStartsWith('https://sessionserver.mojang.com/session/minecraft/profile/86f6e3695b764412a29820cac1d4d0d6', (string)$request2->getUri());
    }

    public function testChangeSkinNotSlim() {
        $this->mockHandler->append(new Response(200));
        $this->api->changeSkin('mocked access token', '86f6e3695b764412a29820cac1d4d0d6', 'http://localhost/skin.png', false);
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.mojang.com/user/profile/86f6e3695b764412a29820cac1d4d0d6/skin', (string)$request->getUri());
        $this->assertSame('Bearer mocked access token', $request->getHeaderLine('Authorization'));
        $body = urldecode($request->getBody()->getContents());
        $this->assertStringContainsString('url=http://localhost/skin.png', $body);
        $this->assertStringNotContainsString('model=slim', $body);
    }

    public function testChangeSkinSlim() {
        $this->mockHandler->append(new Response(200));
        $this->api->changeSkin('mocked access token', '86f6e3695b764412a29820cac1d4d0d6', 'http://localhost/skin.png', true);
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertStringContainsString('model=slim', $request->getBody()->getContents());
    }

    public function testUploadSkinNotSlim() {
        $this->mockHandler->append(new Response(200));
        $this->api->uploadSkin('mocked access token', '86f6e3695b764412a29820cac1d4d0d6', 'skin contents', false);
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.mojang.com/user/profile/86f6e3695b764412a29820cac1d4d0d6/skin', (string)$request->getUri());
        $this->assertSame('Bearer mocked access token', $request->getHeaderLine('Authorization'));
        $this->assertStringNotContainsString('slim', $request->getBody()->getContents());
    }

    public function testUploadSkinSlim() {
        $this->mockHandler->append(new Response(200));
        $this->api->uploadSkin('mocked access token', '86f6e3695b764412a29820cac1d4d0d6', 'skin contents', true);
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.mojang.com/user/profile/86f6e3695b764412a29820cac1d4d0d6/skin', (string)$request->getUri());
        $this->assertStringContainsString('slim', $request->getBody()->getContents());
    }

    public function testResetSkin() {
        $this->mockHandler->append(new Response(200));
        $this->api->resetSkin('mocked access token', '86f6e3695b764412a29820cac1d4d0d6');
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://api.mojang.com/user/profile/86f6e3695b764412a29820cac1d4d0d6/skin', (string)$request->getUri());
        $this->assertSame('Bearer mocked access token', $request->getHeaderLine('Authorization'));
    }

    public function testPlayernamesToUuidsInvalidArgumentException() {
        $names = [];
        for ($i = 0; $i < 101; $i++) {
            $names[] = base64_encode(random_bytes(4));
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You cannot request more than 100 names per request');
        $this->api->playernamesToUuids($names);
    }

    public function testBlockedServers() {
        $this->mockHandler->append(new Response(200, [], trim('
            6f2520f8bd70a718c568ab5274c56bdbbfc14ef4
            7ea72de5f8e70a2ac45f1aa17d43f0ca3cddeedd
            c005ad34245a8f2105658da2d6d6e8545ef0f0de
            c645d6c6430db3069abd291ec13afebdb320714b
        ') . "\n"));

        $result = $this->api->blockedServers();
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://sessionserver.mojang.com/blockedservers', (string)$request->getUri());
        $this->assertCount(4, $result);
    }

    public function testAuthenticate() {
        $this->mockHandler->append($this->createResponse(200, [
            'accessToken' => 'access token value',
            'clientToken' => 'client token value',
            'availableProfiles' => [
                [
                    'id' => '86f6e3695b764412a29820cac1d4d0d6',
                    'name' => 'MockUsername',
                    'legacy' => false,
                ],
            ],
            'selectedProfile' => [
                'id' => '86f6e3695b764412a29820cac1d4d0d6',
                'name' => 'MockUsername',
                'legacy' => false,
            ],
            'user' => [
                'id' => '86f6e3695b764412a29820cac1d4d0d6',
                'properties' => [
                    [
                        'name' => 'preferredLanguage',
                        'value' => 'en',
                    ],
                    [
                        'name' => 'twitch_access_token',
                        'value' => 'twitch oauth token',
                    ],
                ],
            ],
        ]));

        $result = $this->api->authenticate('MockUsername', 'some password', 'client token value');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://authserver.mojang.com/authenticate', (string)$request->getUri());

        $this->assertSame('access token value', $result->getAccessToken());
        $this->assertSame('client token value', $result->getClientToken());

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getAvailableProfiles()[0]->getId());
        $this->assertSame('MockUsername', $result->getAvailableProfiles()[0]->getName());
        $this->assertFalse($result->getAvailableProfiles()[0]->isLegacy());
        $this->assertFalse($result->getAvailableProfiles()[0]->isDemo());

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getSelectedProfile()->getId());
        $this->assertSame('MockUsername', $result->getSelectedProfile()->getName());
        $this->assertFalse($result->getSelectedProfile()->isLegacy());
        $this->assertFalse($result->getSelectedProfile()->isDemo());

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getUser()->getId());

        $this->assertSame('preferredLanguage', $result->getUser()->getProperties()[0]->getName());
        $this->assertSame('en', $result->getUser()->getProperties()[0]->getValue());
        $this->assertSame('twitch_access_token', $result->getUser()->getProperties()[1]->getName());
        $this->assertSame('twitch oauth token', $result->getUser()->getProperties()[1]->getValue());
    }

    public function testAuthenticateWithNotSpecifiedClientToken() {
        $this->mockHandler->append($this->createResponse(200, [
            'accessToken' => 'access token value',
            'clientToken' => 'client token value',
            'availableProfiles' => [],
            'selectedProfile' => [
                'id' => '86f6e3695b764412a29820cac1d4d0d6',
                'name' => 'MockUsername',
                'legacy' => false,
            ],
            'user' => [
                'id' => '86f6e3695b764412a29820cac1d4d0d6',
                'properties' => [],
            ],
        ]));

        $this->api->authenticate('MockUsername', 'some password');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $body = json_decode($request->getBody()->getContents(), true);
        // https://gist.github.com/johnelliott/cf77003f72f889abbc3f32785fa3df8d
        $this->assertRegExp('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i', $body['clientToken']);
    }

    public function testAuthenticateInvalid() {
        $this->mockHandler->append(new Response(403));
        $this->expectException(ForbiddenException::class);
        $this->api->authenticate('MockUsername', 'some password');
    }

    public function testRefreshSuccessful() {
        $this->mockHandler->append($this->createResponse(200, [
            'accessToken' => 'new access token value',
            'clientToken' => 'client token value',
            'selectedProfile' => [
                'id' => '86f6e3695b764412a29820cac1d4d0d6',
                'name' => 'MockUsername',
            ],
            'user' => [
                'id' => '86f6e3695b764412a29820cac1d4d0d6',
                'properties' => [
                    [
                        'name' => 'preferredLanguage',
                        'value' => 'en',
                    ],
                    [
                        'name' => 'twitch_access_token',
                        'value' => 'twitch oauth token',
                    ],
                ],
            ],
        ]));

        $result = $this->api->refresh('access token value', 'client token value');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://authserver.mojang.com/refresh', (string)$request->getUri());

        $this->assertSame('new access token value', $result->getAccessToken());
        $this->assertSame('client token value', $result->getClientToken());

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getSelectedProfile()->getId());
        $this->assertSame('MockUsername', $result->getSelectedProfile()->getName());
        $this->assertFalse($result->getSelectedProfile()->isLegacy());
        $this->assertFalse($result->getSelectedProfile()->isDemo());

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getUser()->getId());

        $this->assertSame('preferredLanguage', $result->getUser()->getProperties()[0]->getName());
        $this->assertSame('en', $result->getUser()->getProperties()[0]->getValue());
        $this->assertSame('twitch_access_token', $result->getUser()->getProperties()[1]->getName());
        $this->assertSame('twitch oauth token', $result->getUser()->getProperties()[1]->getValue());
    }

    public function testRefreshInvalid() {
        $this->mockHandler->append(new Response(403));
        $this->expectException(ForbiddenException::class);
        $this->api->refresh('access token value', 'client token value');
    }

    public function testValidateSuccessful() {
        $this->mockHandler->append(new Response(204));

        $result = $this->api->validate('mocked access token');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://authserver.mojang.com/validate', (string)$request->getUri());

        $this->assertTrue($result);
    }

    public function testValidateInvalid() {
        $this->mockHandler->append(new Response(403));

        $result = $this->api->validate('mocked access token');

        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $this->assertSame('https://authserver.mojang.com/validate', (string)$request->getUri());

        $this->assertFalse($result);
    }

    public function testInvalidateSuccessful() {
        $this->mockHandler->append(new Response(200));
        $this->api->invalidate('mocked access token', 'mocked client token');
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $params = json_decode($request->getBody()->getContents(), true);

        $this->assertSame('https://authserver.mojang.com/invalidate', (string)$request->getUri());

        $this->assertSame('mocked access token', $params['accessToken']);
        $this->assertSame('mocked client token', $params['clientToken']);
    }

    public function testSignoutSuccessful() {
        $this->mockHandler->append(new Response(204));
        $result = $this->api->signout('MockUsername', 'some password');
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];

        $this->assertSame('https://authserver.mojang.com/signout', (string)$request->getUri());

        $this->assertTrue($result);
    }

    public function testSignoutInvalid() {
        $this->mockHandler->append(new Response(403));
        $result = $this->api->signout('MockUsername', 'some password');
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];

        $this->assertSame('https://authserver.mojang.com/signout', (string)$request->getUri());

        $this->assertFalse($result);
    }

    public function testJoinServer() {
        $this->mockHandler->append(new Response(200));
        $this->api->joinServer('mocked access token', '86f6e3695b764412a29820cac1d4d0d6', 'ad72fe1efe364e6eb78c644a9fba1d30');
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $body = $request->getBody()->getContents();
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString('
            {
                "accessToken": "mocked access token",
                "selectedProfile": "86f6e3695b764412a29820cac1d4d0d6",
                "serverId": "ad72fe1efe364e6eb78c644a9fba1d30"
            }
        ', $body);
    }

    public function testHasJoinedServer() {
        $this->mockHandler->append($this->createResponse(200, [
            'id' => '86f6e3695b764412a29820cac1d4d0d6',
            'name' => 'MockUsername',
            'properties' => [
                [
                    'name' => 'textures',
                    'value' => base64_encode(json_encode([
                        'timestamp' => 1553961848860,
                        'profileId' => '86f6e3695b764412a29820cac1d4d0d6',
                        'profileName' => 'MockUsername',
                        'signatureRequired' => true,
                        'textures' => [
                            'SKIN' => [
                                'url' => 'http://textures.minecraft.net/texture/292009a4925b58f02c77dadc3ecef07ea4c7472f64e0fdc32ce5522489362680',
                            ],
                        ],
                    ])),
                    'signature' => 'mocked signature value',
                ],
            ],
        ]));
        $result = $this->api->hasJoinedServer('MockedUsername', 'ad72fe1efe364e6eb78c644a9fba1d30');
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = $this->history[0]['request'];
        $params = parse_query($request->getUri()->getQuery());
        $this->assertSame('MockedUsername', $params['username']);
        $this->assertSame('ad72fe1efe364e6eb78c644a9fba1d30', $params['serverId']);

        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $result->getId());
        $this->assertSame('MockUsername', $result->getName());
        $props = $result->getProps();
        /** @var TexturesProperty $texturesProperty */
        $texturesProperty = $props[0];
        $this->assertInstanceOf(TexturesProperty::class, $texturesProperty);
        $this->assertSame('textures', $texturesProperty->getName());
        $this->assertSame('mocked signature value', $texturesProperty->getSignature());
        $textures = $texturesProperty->getTextures();
        $this->assertSame(1553961848, $textures->getTimestamp());
        $this->assertSame('86f6e3695b764412a29820cac1d4d0d6', $textures->getProfileId());
        $this->assertSame('MockUsername', $textures->getProfileName());
        $this->assertTrue($textures->isSignatureRequired());
        $this->assertNotNull($textures->getSkin());
        $this->assertSame(
            'http://textures.minecraft.net/texture/292009a4925b58f02c77dadc3ecef07ea4c7472f64e0fdc32ce5522489362680',
            $textures->getSkin()->getUrl()
        );
        $this->assertFalse($textures->getSkin()->isSlim());
        $this->assertNull($textures->getCape());
    }

    public function testHasJoinedServerEmptyResponse() {
        $this->mockHandler->append(new Response(200));
        $this->expectException(NoContentException::class);
        $this->api->hasJoinedServer('MockedUsername', 'ad72fe1efe364e6eb78c644a9fba1d30');
    }

    public function testGetClient() {
        $child = new class extends Api {
            public function getDefaultClient() {
                return $this->getClient();
            }
        };
        $this->assertInstanceOf(ClientInterface::class, $child->getDefaultClient());
    }

    private function createResponse(int $statusCode, array $response): ResponseInterface {
        return new Response($statusCode, ['content-type' => 'json'], json_encode($response));
    }

}
