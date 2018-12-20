<?php

namespace Spatie\Once\Test;

use Spatie\Once\Cache;
use PHPUnit\Framework\TestCase;

class OnceTest extends TestCase
{
    /** @test */
    public function it_will_run_the_a_callback_without_arguments_only_once()
    {
        $testClass = new class() {
            public function getNumber()
            {
                return once(function () {
                    return rand(1, 10000000);
                });
            }
        };

        $firstResult = $testClass->getNumber();

        $this->assertGreaterThanOrEqual(1, $firstResult);
        $this->assertLessThanOrEqual(10000000, $firstResult);

        foreach (range(1, 100) as $i) {
            $this->assertEquals($firstResult, $testClass->getNumber());
        }
    }

    /** @test */
    public function it_will_run_the_given_callback_only_once_per_variation_arguments_in_use()
    {
        $testClass = new class() {
            public function getNumberForLetter($letter)
            {
                return once(function () use ($letter) {
                    return $letter.rand(1, 10000000);
                });
            }
        };

        foreach (range('A', 'Z') as $letter) {
            $firstResult = $testClass->getNumberForLetter($letter);
            $this->assertStringStartsWith($letter, $firstResult);

            foreach (range(1, 100) as $i) {
                $this->assertEquals($firstResult, $testClass->getNumberForLetter($letter));
            }
        }
    }

    /** @test */
    public function it_will_run_the_given_callback_only_once_for_falsy_result()
    {
        $testClass = new class() {
            public $counter = 0;

            public function getNull()
            {
                return once(function () {
                    $this->counter++;
                });
            }
        };

        $this->assertNull($testClass->getNull());
        $this->assertNull($testClass->getNull());
        $this->assertNull($testClass->getNull());

        $this->assertEquals(1, $testClass->counter);
    }

    /** @test */
    public function it_will_work_properly_with_unset_objects()
    {
        $previousNumbers = [];

        foreach (range(1, 5) as $number) {
            $testClass = new TestClass();

            $number = $testClass->getRandomNumber();

            $this->assertNotContains($number, $previousNumbers);

            $previousNumbers[] = $number;

            unset($testClass);
        }
    }

    /** @test */
    public function it_will_remember_the_memoized_value_when_serialized_when_called_in_the_same_request()
    {
        $testClass = new TestClass();

        $firstNumber = $testClass->getRandomNumber();

        $this->assertEquals($firstNumber, $testClass->getRandomNumber());

        $serialized = serialize($testClass);
        $unserialized = unserialize($serialized);
        unset($unserialized);

        $this->assertEquals($firstNumber, $testClass->getRandomNumber());
    }

    /** @test */
    public function it_will_not_try_to_forget_something_in_the_cache_when_called_in_another_request()
    {
        $testClass = new TestClass();

        $firstNumber = $testClass->getRandomNumber();

        $objectHash = spl_object_hash($testClass);

        $serialized = serialize($testClass);
        unset($testClass);

        $unserialized = unserialize($serialized);
        $unserializedObjectHash = spl_object_hash($unserialized);

        Cache::$values = [$unserializedObjectHash => ['abc' => 'dummy']];

        unset($unserialized);

        $this->assertArrayHasKey($unserializedObjectHash, Cache::$values);
    }

    /** @test */
    public function it_will_run_callback_once_on_static_method()
    {
        $object = new class() {
            public static function getNumber()
            {
                return once(function () {
                    return rand(1, 10000000);
                });
            }
        };
        $class = get_class($object);

        $firstResult = $class::getNumber();

        $this->assertGreaterThanOrEqual(1, $firstResult);
        $this->assertLessThanOrEqual(10000000, $firstResult);

        foreach (range(1, 100) as $i) {
            $this->assertEquals($firstResult, $class::getNumber());
        }
    }

    /** @test */
    public function it_will_run_callback_once_on_static_method_per_variation_arguments_in_use()
    {
        $object = new class() {
            public static function getNumberForLetter($letter)
            {
                return once(function () use ($letter) {
                    return $letter.rand(1, 10000000);
                });
            }
        };
        $class = get_class($object);

        foreach (range('A', 'Z') as $letter) {
            $firstResult = $class::getNumberForLetter($letter);
            $this->assertStringStartsWith($letter, $firstResult);

            foreach (range(1, 100) as $i) {
                $this->assertEquals($firstResult, $class::getNumberForLetter($letter));
            }
        }
    }
}
