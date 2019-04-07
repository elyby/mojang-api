<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

use DateTime;

class NameHistoryItem {

    /**
     * @var string
     */
    private $name;

    /**
     * @var DateTime|null
     */
    private $changedToAt;

    public function __construct(string $name, ?DateTime $changedToAt) {
        $this->name = $name;
        $this->changedToAt = $changedToAt;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getChangedToAt(): ?DateTime {
        return $this->changedToAt;
    }

}
