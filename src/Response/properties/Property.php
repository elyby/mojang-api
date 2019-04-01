<?php
declare(strict_types=1);

namespace Ely\Mojang\Response\Properties;

class Property {

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $value;

    public function __construct(array $prop) {
        $this->name = $prop['name'];
        $this->value = $prop['value'];
    }

    public function getName(): string {
        return $this->name;
    }

    public function getValue(): string {
        return $this->value;
    }

}
