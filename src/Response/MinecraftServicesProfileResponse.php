<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

/**
 * @see https://wiki.vg/Mojang_API#Profile_Information
 */
class MinecraftServicesProfileResponse {

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var MinecraftServicesProfileSkin[]
     */
    private $skins;

    /**
     * @var MinecraftServicesProfileCape[]
     */
    private $capes;

    /**
     * @param string $id
     * @param string $name
     * @param MinecraftServicesProfileSkin[] $skins
     * @param MinecraftServicesProfileCape[] $capes
     */
    public function __construct(string $id, string $name, array $skins, array $capes) {
        $this->id = $id;
        $this->name = $name;
        $this->skins = $skins;
        $this->capes = $capes;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @return MinecraftServicesProfileSkin[]
     */
    public function getSkins(): array {
        return $this->skins;
    }

    /**
     * @return MinecraftServicesProfileCape[]
     */
    public function getCapes(): array {
        return $this->capes;
    }

}
