<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class ProfileInfo {

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isLegacy;

    /**
     * @var bool
     */
    private $isDemo;

    public function __construct(string $id, string $name, bool $isLegacy = false, bool $isDemo = false) {
        $this->id = $id;
        $this->name = $name;
        $this->isLegacy = $isLegacy;
        $this->isDemo = $isDemo;
    }

    public static function createFromResponse(array $response): self {
        return new static(
            $response['id'],
            $response['name'],
            $response['legacy'] ?? false,
            $response['demo'] ?? false
        );
    }

    /**
     * @return string user's uuid without dashes
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return string username at the current time
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return bool true means, that account not migrated into Mojang account
     */
    public function isLegacy(): bool {
        return $this->isLegacy;
    }

    /**
     * @return bool true means, that account now in demo mode (not premium user)
     */
    public function isDemo(): bool {
        return $this->isDemo;
    }

}
