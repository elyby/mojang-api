<?php
declare(strict_types=1);

namespace Ely\Mojang\Test\Response\Properties;

use Ely\Mojang\Response\Properties\Factory;
use Ely\Mojang\Response\Properties\Property;
use Ely\Mojang\Response\Properties\TexturesProperty;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase {

    /**
     * @param array $inputProps
     * @param string $expectedType
     *
     * @dataProvider getProps
     */
    public function testCreate(array $inputProps, string $expectedType) {
        $this->assertInstanceOf($expectedType, Factory::createFromProp($inputProps));
    }

    public function getProps(): iterable {
        yield [[
            'name' => 'textures',
            'value' => 'value',
            'signature' => '123',
        ], TexturesProperty::class];

        yield [[
            'name' => 'other',
            'value' => 'value',
        ], Property::class];
    }

}
