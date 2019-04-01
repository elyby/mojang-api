<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class AuthenticateResponse {

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $clientToken;

    /**
     * @var array
     */
    private $rawAvailableProfiles;

    /**
     * @var array
     */
    private $rawSelectedProfile;

    /**
     * @var array
     */
    private $rawUser;

    public function __construct(
        string $accessToken,
        string $clientToken,
        array $availableProfiles,
        array $selectedProfile,
        array $user
    ) {
        $this->accessToken = $accessToken;
        $this->clientToken = $clientToken;
        $this->rawAvailableProfiles = $availableProfiles;
        $this->rawSelectedProfile = $selectedProfile;
        $this->rawUser = $user;
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function getClientToken(): string {
        return $this->clientToken;
    }

    /**
     * @return ProfileInfo[]
     */
    public function getAvailableProfiles(): array {
        return array_map([ProfileInfo::class, 'createFromResponse'], $this->rawAvailableProfiles);
    }

    public function getSelectedProfile(): ProfileInfo {
        return ProfileInfo::createFromResponse($this->rawSelectedProfile);
    }

    public function getUser(): AuthenticationResponseUserField {
        return new AuthenticationResponseUserField($this->rawUser['id'], $this->rawUser['properties']);
    }

}
