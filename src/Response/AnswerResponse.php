<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class AnswerResponse {

    /**
     * @var int
     */
    public $id;

    /**
     * @var string|null
     */
    public $answer;

    public function __construct(int $id, ?string $answer = null) {
        $this->id = $id;
        $this->answer = $answer;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getAnswer(): ?string {
        return $this->answer;
    }

}
