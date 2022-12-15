<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

/**
 * @see https://wiki.vg/Mojang_API#Profile_Information
 */
class MinecraftServicesProfileSkin {

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $variant;

    /**
     * @var string|null
     */
    private $alias;

    public function __construct(string $id, string $state, string $url, string $variant, ?string $alias) {
        $this->id = $id;
        $this->state = $state;
        $this->url = $url;
        $this->variant = $variant;
        $this->alias = $alias;
    }

    public function getId(): string {
        return $this->id;
    }

    /**
     * TODO: figure out literal for not active state
     * @return 'ACTIVE'
     */
    public function getState(): string {
        return $this->state;
    }

    public function getUrl(): string {
        return $this->url;
    }

    /**
     * @return 'CLASSIC'|'SLIM'
     */
    public function getVariant(): string {
        return $this->variant;
    }

    /**
     * Doesn't show up for some reason for some accounts
     */
    public function getAlias(): ?string {
        return $this->alias;
    }

}
