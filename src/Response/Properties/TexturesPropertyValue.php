<?php
declare(strict_types=1);

namespace Ely\Mojang\Response\Properties;

class TexturesPropertyValue {

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var array
     */
    private $textures;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var bool
     */
    private $signatureRequired;

    public function __construct(
        string $profileId,
        string $profileName,
        array $textures,
        int $timestamp,
        bool $signatureRequired = false
    ) {
        $this->id = $profileId;
        $this->username = $profileName;
        $this->textures = $textures;
        $this->timestamp = (int)floor($timestamp / 1000);
        $this->signatureRequired = $signatureRequired;
    }

    public static function createFromRawTextures(string $rawTextures): self {
        $decoded = json_decode(base64_decode($rawTextures), true);
        return new static(
            $decoded['profileId'],
            $decoded['profileName'],
            $decoded['textures'],
            $decoded['timestamp'],
            $decoded['signatureRequired'] ?? false
        );
    }

    public function getProfileId(): string {
        return $this->id;
    }

    public function getProfileName(): string {
        return $this->username;
    }

    public function getTimestamp(): int {
        return $this->timestamp;
    }

    public function isSignatureRequired(): bool {
        return $this->signatureRequired;
    }

    public function getSkin(): ?TexturesPropertyValueSkin {
        if (!isset($this->textures['SKIN'])) {
            return null;
        }

        return TexturesPropertyValueSkin::createFromTextures($this->textures['SKIN']);
    }

    public function getCape(): ?TexturesPropertyValueCape {
        if (!isset($this->textures['CAPE'])) {
            return null;
        }

        return new TexturesPropertyValueCape($this->textures['CAPE']['url']);
    }

}
