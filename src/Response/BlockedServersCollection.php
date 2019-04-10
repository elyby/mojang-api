<?php
declare(strict_types=1);

namespace Ely\Mojang\Response;

use ArrayAccess;
use Countable;
use InvalidArgumentException;

class BlockedServersCollection implements ArrayAccess, Countable {

    /**
     * @var string[]
     */
    private $hashes;

    public function __construct(array $hashes) {
        $this->hashes = $hashes;
    }

    public function offsetExists($offset): bool {
        return isset($this->hashes[$offset]);
    }

    public function offsetGet($offset): string {
        return $this->hashes[$offset];
    }

    public function offsetSet($offset, $value): void {
        $this->hashes[$offset] = $value;
    }

    public function offsetUnset($offset): void {
        unset($this->hashes[$offset]);
    }

    public function count(): int {
        return count($this->hashes);
    }

    /**
     * @param string $serverName
     *
     * @return bool
     *
     * @link https://wiki.vg/Mojang_API#Blocked_Servers
     */
    public function isBlocked(string $serverName): bool {
        if (filter_var($serverName, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new InvalidArgumentException('Minecraft does not support IPv6, so this library too');
        }

        $isIp = filter_var($serverName, FILTER_VALIDATE_IP) !== false;
        foreach ($this->generateSubstitutions(mb_strtolower($serverName), $isIp) as $mask) {
            $hash = sha1($mask);
            if (in_array($hash, $this->hashes, true)) {
                return true;
            }
        }

        return false;
    }

    private function generateSubstitutions(string $input, bool $right): iterable {
        yield $input;
        $parts = explode('.', $input);
        while (count($parts) > 1) {
            if ($right) {
                array_pop($parts);
                yield implode('.', $parts) . '.*';
            } else {
                array_shift($parts);
                yield '*.' . implode('.', $parts);
            }
        }
    }

}
