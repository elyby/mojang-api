<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

use Ely\Mojang\Response\Properties\Factory;

class AuthenticationResponseUserField {

    private $id;

    private $rawProperties;

    public function __construct(string $id, array $rawProperties) {
        $this->id = $id;
        $this->rawProperties = $rawProperties;
    }

    public function getId(): string {
        return $this->id;
    }

    /**
     * @return \Ely\Mojang\Response\Properties\Property[]
     */
    public function getProperties(): array {
        return array_map([Factory::class, 'createFromProp'], $this->rawProperties);
    }

}
