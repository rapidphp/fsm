<?php

namespace Rapid\Fsm;

class AttributeResolver
{
    public static function has($reflection, string $type): bool
    {
        /** @var \ReflectionAttribute $attribute */
        foreach ($reflection->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), $type, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template T
     * @param $reflection
     * @param class-string<T> $type
     * @return T|object|null
     */
    public static function get($reflection, string $type): ?object
    {
        /** @var \ReflectionAttribute $attribute */
        foreach ($reflection->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), $type, true)) {
                return $attribute->newInstance();
            }
        }

        return null;
    }

    /**
     * @template T
     * @param $reflection
     * @param class-string<T> $type
     * @return array<T|object>
     */
    public static function all($reflection, string $type): array
    {
        $all = [];

        /** @var \ReflectionAttribute $attribute */
        foreach ($reflection->getAttributes() as $attribute) {
            if (is_a($attribute->getName(), $type, true)) {
                $all[] = $attribute->newInstance();
            }
        }

        return $all;
    }
}