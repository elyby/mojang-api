<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class QuestionResponse {

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $question;

    public function __construct(int $id, string $question) {
        $this->id = $id;
        $this->question = $question;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getQuestion(): string {
        return $this->question;
    }

}
