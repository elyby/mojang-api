<?php
declare(strict_types=1);

namespace Ely\Mojang\Test\Response;

use Ely\Mojang\Response\BlockedServersCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BlockedServersCollectionTest extends TestCase {

    public function testArrayAccess() {
        $model = new BlockedServersCollection(['1', '2', '3']);
        $this->assertTrue(isset($model[0]));
        $this->assertFalse(isset($model[65535]));
        $this->assertSame('1', $model[0]);
        $this->assertSame('2', $model[1]);
        unset($model[0]);
        $this->assertFalse(isset($model[0]));
        $model[3] = 'find me';
        $this->assertSame('find me', $model[3]);
    }

    public function testCountable() {
        $model = new BlockedServersCollection(['1', '2', '3']);
        $this->assertCount(3, $model);
    }

    /**
     * @dataProvider getIsBlockedCases
     */
    public function testIsBlocked(string $serverName, bool $expectedResult) {
        $model = new BlockedServersCollection([
            '6f2520f8bd70a718c568ab5274c56bdbbfc14ef4', // *.minetime.com
            '48f04e89d20b15de115503f22fedfe2cb2d1ab12', // brandonisan.unusualperson.com
            '4ca799b162d4ebdf2ec5e0ece2ed51fba5a3db65', // 136.243.*
            'b7a822278e90205f016c1b028122e222f836641b', // 147.117.184.134
        ]);
        $this->assertSame($expectedResult, $model->isBlocked($serverName));
    }

    public function getIsBlockedCases() {
        yield ['mc.minetime.com', true];
        yield ['MC.MINETIME.COM', true];
        yield ['sub.mc.minetime.com', true];
        yield ['minetime.com', false];
        yield ['minetime.mc.com', false];

        yield ['brandonisan.unusualperson.com', true];
        yield ['other.unusualperson.com', false];

        yield ['136.243.88.97', true];
        yield ['136.244.88.97', false];

        yield ['147.117.184.134', true];
        yield ['147.117.184.135', false];
    }

    public function testIsBlockedWithIPv6() {
        $model = new BlockedServersCollection([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minecraft does not support IPv6, so this library too');
        $model->isBlocked('d860:5df:9447:61b3:d1dd:1170:146a:bcc');
    }

}
