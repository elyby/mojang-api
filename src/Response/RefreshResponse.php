<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class RefreshResponse {

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
    private $rawSelectedProfile;

    /**
     * @var array
     */
    private $rawUser;

    public function __construct(
        string $accessToken,
        string $clientToken,
        array $selectedProfile,
        array $user
    ) {
        $this->accessToken = $accessToken;
        $this->clientToken = $clientToken;
        $this->rawSelectedProfile = $selectedProfile;
        $this->rawUser = $user;
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function getClientToken(): string {
        return $this->clientToken;
    }

    public function getSelectedProfile(): ProfileInfo {
        return ProfileInfo::createFromResponse($this->rawSelectedProfile);
    }

    public function getUser(): AuthenticationResponseUserField {
        return new AuthenticationResponseUserField($this->rawUser['id'], $this->rawUser['properties']);
    }

}
