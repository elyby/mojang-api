<?php
declare(strict_types=1);

namespace Ely\Mojang\Response\Properties;

class TexturesPropertyValueCape {

    /**
     * @var string
     */
    private $url;

    public function __construct(string $skinUrl) {
        $this->url = $skinUrl;
    }

    public function getUrl(): string {
        return $this->url;
    }

}
