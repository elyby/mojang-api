<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

use Ely\Mojang\Response\Properties\Factory;

class ProfileResponse {

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $props;

    public function __construct(string $id, string $name, array $rawProps) {
        $this->id = $id;
        $this->name = $name;
        $this->props = $rawProps;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @return \Ely\Mojang\Response\Properties\Property[]
     */
    public function getProps(): array {
        return array_map([Factory::class, 'createFromProp'], $this->props);
    }

}
