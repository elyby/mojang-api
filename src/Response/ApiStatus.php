<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class ApiStatus {

    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var string
     */
    private $status;

    public function __construct(string $serviceName, string $status) {
        $this->serviceName = $serviceName;
        $this->status = $status;
    }

    public function getServiceName(): string {
        return $this->serviceName;
    }

    public function getStatus(): string {
        return $this->status;
    }

    /**
     * Asserts that current service has no issues.
     *
     * @return bool
     */
    public function isGreen(): bool {
        return $this->assertStatusIs('green');
    }

    /**
     * Asserts that current service has some issues.
     *
     * @return bool
     */
    public function isYellow(): bool {
        return $this->assertStatusIs('yellow');
    }

    /**
     * Asserts that current service is unavailable.
     *
     * @return bool
     */
    public function isRed(): bool {
        return $this->assertStatusIs('red');
    }

    private function assertStatusIs(string $expectedStatus): bool {
        return $this->getStatus() === $expectedStatus;
    }

}
