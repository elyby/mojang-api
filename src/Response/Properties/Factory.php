<?php
declare(strict_types=1);

namespace Ely\Mojang\Response\Properties;

class Factory {

    private static $MAP = [
        'textures' => TexturesProperty::class,
    ];

    public static function createFromProp(array $prop): Property {
        $name = $prop['name'];
        if (isset(self::$MAP[$name])) {
            $className = self::$MAP[$name];
            return new $className($prop);
        }

        return new Property($prop);
    }

}
