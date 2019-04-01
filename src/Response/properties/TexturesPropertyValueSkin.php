<?php
declare(strict_types=1);

namespace Ely\Mojang\Response\Properties;

class TexturesPropertyValueSkin {

    /**
     * @var string
     */
    private $url;

    /**
     * @var bool
     */
    private $isSlim;

    public function __construct(string $skinUrl, bool $isSlim = false) {
        $this->url = $skinUrl;
        $this->isSlim = $isSlim;
    }

    public static function createFromTextures(array $textures): self {
        $model = &$textures['metainfo']['model']; // ampersand to avoid notice about unexpected key
        return new static($textures['url'], $model === 'slim');
    }

    public function getUrl(): string {
        return $this->url;
    }

    public function isSlim(): bool {
        return $this->isSlim;
    }

}
