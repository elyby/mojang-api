<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class StatisticsResponse {

    /**
     * @var int
     */
    private $total;

    /**
     * @var int
     */
    private $last24h;

    /**
     * @var float
     */
    private $saleVelocityPerSeconds;

    public function __construct(int $total, int $last24h, float $saleVelocityPerSeconds) {
        $this->total = $total;
        $this->last24h = $last24h;
        $this->saleVelocityPerSeconds = $saleVelocityPerSeconds;
    }

    public function getTotal(): int {
        return $this->total;
    }

    public function getLast24H(): int {
        return $this->last24h;
    }

    public function getSaleVelocityPerSeconds(): float {
        return $this->saleVelocityPerSeconds;
    }

}
