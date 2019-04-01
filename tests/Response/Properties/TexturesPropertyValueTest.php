<?php
declare(strict_types=1);

namespace Ely\Mojang\Test\Response\Properties;

use Ely\Mojang\Response\Properties\TexturesPropertyValue;
use PHPUnit\Framework\TestCase;

class TexturesPropertyValueTest extends TestCase {

    public function testGetSkin() {
        $object = new TexturesPropertyValue('', '', [
            'SKIN' => [
                'url' => 'skin url',
            ],
        ], 0);
        $this->assertNotNull($object->getSkin());
        $this->assertSame('skin url', $object->getSkin()->getUrl());
        $this->assertFalse($object->getSkin()->isSlim());

        $object = new TexturesPropertyValue('', '', [
            'SKIN' => [
                'url' => 'skin url',
                'metainfo' => [
                    'model' => 'slim',
                ],
            ],
        ], 0);
        $this->assertNotNull($object->getSkin());
        $this->assertSame('skin url', $object->getSkin()->getUrl());
        $this->assertTrue($object->getSkin()->isSlim());

        $object = new TexturesPropertyValue('', '', [], 0);
        $this->assertNull($object->getSkin());
    }

    public function testGetCape() {
        $object = new TexturesPropertyValue('', '', [
            'CAPE' => [
                'url' => 'cape url',
            ],
        ], 0);
        $this->assertNotNull($object->getCape());
        $this->assertSame('cape url', $object->getCape()->getUrl());

        $object = new TexturesPropertyValue('', '', [], 0);
        $this->assertNull($object->getCape());
    }

}
