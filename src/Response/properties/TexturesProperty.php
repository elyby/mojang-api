<?php
declare(strict_types=1);

namespace Ely\Mojang\Response\Properties;

class TexturesProperty extends Property {

    /**
     * @var string|null
     */
    private $signature;

    public function __construct(array $prop) {
        parent::__construct($prop);
        $this->signature = $prop['signature'] ?? null;
    }

    public function getTextures(): TexturesPropertyValue {
        return TexturesPropertyValue::createFromRawTextures($this->value);
    }

    public function getSignature(): ?string {
        return $this->signature;
    }

}
