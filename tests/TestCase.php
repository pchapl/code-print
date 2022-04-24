<?php

declare(strict_types=1);

namespace Pchapl\CodePrint\Tests;

use ReflectionClass;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    final protected static function assertEqualsButNotSame(mixed $expected, mixed $actual): void
    {
        self::assertNotSame($expected, $actual);
        self::assertEquals($expected, $actual);
    }

    final protected static function assertAllSame(mixed $expected, mixed ...$args): void
    {
        foreach ($args as $k => $arg) {
            self::assertSame($expected, $arg, "Argument k = $k is not the same with \$expected");
        }
    }

    final protected function getPrivateProperty(object $object, string $property): mixed
    {
        $reflectionProperty = (new ReflectionClass($object))->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}
