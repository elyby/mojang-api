<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

class QuestionResponse {

    /**
     * @var int
     */
    private $questionId;

    /**
     * @var string
     */
    private $question;

    /**
     * @var int
     */
    private $answerId;

    public function __construct(int $questionId, string $question, int $answerId) {
        $this->questionId = $questionId;
        $this->question = $question;
        $this->answerId = $answerId;
    }

    public function getQuestionId(): int {
        return $this->questionId;
    }

    public function getQuestion(): string {
        return $this->question;
    }

    public function getAnswerId(): int {
        return $this->answerId;
    }

}
