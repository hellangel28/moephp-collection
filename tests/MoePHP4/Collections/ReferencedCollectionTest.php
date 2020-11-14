<?php


namespace tests\MoePHP4\Collection;


use MoePHP4\Collections\Collection;
use MoePHP4\Collections\Exception\CollectionException;
use MoePHP4\Collections\ReferencedCollection;
use PHPUnit\Framework\TestCase;


/**
 * Class ReferencedCollectionTest
 * @package tests\MoePHP4\Collections
 */
class ReferencedCollectionTest extends TestCase
{

    /**
     * @throws CollectionException
     */
    public function testStaticUnwrap()
    {
        $arr = [1, 2, 3];
        $collection = new ReferencedCollection($arr);
        $unwrapped = ReferencedCollection::unwrap($collection);
        $this->assertSame(count($arr), count($unwrapped));
    }

    /**
     * @throws CollectionException
     */
    public function testStaticUnwrap_notArrayOrCollection()
    {
        $this->expectException(CollectionException::class);
        $this->expectExceptionMessage('Can only unwrap collections and arrays');
        $wrapped = "test";
        $unwrapped = ReferencedCollection::unwrap($wrapped);
        $this->assertSame($unwrapped, $wrapped);
    }

    /**
     *
     */
    public function testStaticUnwrap_isReference()
    {
        $arr = [1, 2, 3];
        $collection = new ReferencedCollection($arr);
        $unwrapped = ReferencedCollection::unwrap($collection);
        $this->assertSame(3, count($unwrapped));
        unset($arr[0]);
        $unwrapped = ReferencedCollection::unwrap($collection);
        $this->assertSame(2, count($unwrapped));
    }

    /**
     *
     */
    public function testStaticUnwrap_array()
    {
        $arr = [1, 2, 3];
        $unwrapped = ReferencedCollection::unwrap($arr);
        $this->assertSame(3, count($unwrapped));

    }


    // non-static methods

    /**
     *
     */
    public function testConstruct_Reference()
    {
        $arr = [1, 2, 3];
        $collection = new ReferencedCollection($arr);
        unset($arr[0]);
        $this->assertSame(2, $collection->count());
    }

    /**
     *
     */
    public function testCreateWithoutReference()
    {
        $array = [1, 2, 3];
        $withoutRef = new Collection($array);
        unset($array[0]);
        $this->assertSame(3, $withoutRef->count());
    }

    /**
     *
     */
    public function testAccumulate()
    {
        $array = [1, 2, 3, 4, 5];
        $collection = new ReferencedCollection($array);
        $result = $collection->accumulate(function ($acc, $current) {
            return $acc + $current;
        });
        $this->assertSame(15.0, $result);
    }

    public function testAverage()
    {

        $collection = new Collection([1, 1, 1, 1, 1, 5, 5, 5, 5, 5]);
        $this->assertSame(3.0, $collection->average());
    }

    /**
     *
     */
    public function testChunk()
    {
        $array = [1, 2, 3, 4, 5, 6, 7];
        $collection = new ReferencedCollection($array);
        $chunks = $collection->chunk(2);
        $this->assertSame(4, $chunks->count());

        $this->assertSame(2, $chunks[0]->count());
        $this->assertSame(2, $chunks[1]->count());
        $this->assertSame(2, $chunks[2]->count());
        $this->assertSame(1, $chunks[3]->count());
        $this->assertFalse(array_key_exists(4, $chunks->getArrayReference()));

    }

    /**
     *
     */
    public function testClear()
    {
        $array = [1, 2, 3, 4, 5];
        $collection = new ReferencedCollection($array);
        $collection->clear();
        $this->assertSame(0, count($array));
    }

    /**
     *
     */
    public function testConcat()
    {
        $a = [1, 2, 3];
        $b = [4, 5, 6];

        $collection = new ReferencedCollection($a);
        $collection->concat($b);
        $this->assertSame(3, $collection->count());
        $this->assertSame(4, $collection->get(0));
        $this->assertSame(5, $collection->get(1));
        $this->assertSame(6, $collection->get(2));
    }

    /**
     *
     */
    public function testConcat_noOverwriteKeys()
    {
        $a = [1, 2, 3];
        $b = [4, 5, 6];

        $collection = new ReferencedCollection($a);
        $collection->concat($b, false);
        $this->assertSame(3, $collection->count());
        $this->assertSame(1, $collection->get(0));
        $this->assertSame(2, $collection->get(1));
        $this->assertSame(3, $collection->get(2));
    }


    /**
     *
     */
    public function testConcatCollection()
    {
        $a = [1, 2, 3];
        $b = [4, 5, 6];

        $collection = new ReferencedCollection($a);
        $collection->concatCollection(new ReferencedCollection($b));
        $this->assertSame(3, $collection->count());
        $this->assertSame(4, $collection->get(0));
        $this->assertSame(5, $collection->get(1));
        $this->assertSame(6, $collection->get(2));
    }

    /**
     *
     */
    public function testConcatCollection_noOverwriteKeys()
    {
        $a = [1, 2, 3];
        $b = [4, 5, 6];

        $collection = new ReferencedCollection($a);
        $collection->concatCollection(new ReferencedCollection($b), false);
        $this->assertSame(3, $collection->count());
        $this->assertSame(1, $collection->get(0));
        $this->assertSame(2, $collection->get(1));
        $this->assertSame(3, $collection->get(2));
    }

    /**
     *
     */
    public function testContains()
    {
        $collection = new Collection(["test", "another"]);
        $this->assertTrue($collection->contains('test'));
        $this->assertTrue($collection->contains('another'));
    }

    /**
     *
     */
    public function testCopy()
    {
        $array = [1, 2, 3, 4];
        $collection = new ReferencedCollection($array);
        $copy = $collection->copy();
        unset($array[0]);
        $this->assertSame(3, $collection->count());
        $this->assertSame(4, $copy->count());
    }

    /**
     *
     */
    public function testCount()
    {
        $array = [1, 2, 3];
        $test = new ReferencedCollection($array);
        $this->assertSame(3, $test->count());
        $array = [1, 2, 3, 4, 5];
        $test = new ReferencedCollection($array);
        $this->assertSame(5, $test->count());

    }


    /**
     *
     */
    public function testExtract()
    {
        $collection = new Collection([
            "a" => "one",
            "b" => "two",
            "c" => "three",
        ]);

        $only = $collection->extract(['a', 'b']);
        $this->assertSame(2, $only->count());
        $this->assertSame('one', $only->get("a"));
        $this->assertSame('two', $only->get("b"));
        $this->assertFalse($only->keyExists("c"));

        $this->assertTrue($collection->keyExists("c"));
        $this->assertFalse($collection->keyExists("a"));
        $this->assertFalse($collection->keyExists("b"));
        $this->assertSame(1, $collection->count());
    }


    /**
     *
     */
    public function testFilter()
    {
        $array = [1, 2, 3, 4, 5];
        $test = new ReferencedCollection($array);
        $test->filter(function (int $value) {
            return $value > 3;
        });
        $this->assertSame(2, $test->count());
    }

    /**
     *
     */
    public function testFind()
    {
        $array = ['test', 'abc', 'bla'];
        $collection = new ReferencedCollection($array);
        $result = $collection->find(function ($value) {
            return ($value == 'abc');
        });
        $this->assertSame($result, 'abc');
    }


    /**
     *
     */
    public function testFind_notFound()
    {
        $array = ['test', 'abc', 'bla'];
        $collection = new ReferencedCollection($array);
        $result = $collection->find(function ($value) {
            return ($value == 'defg');
        });
        $this->assertFalse($result);
    }


    public function testFindClosest()
    {
        $array = ['test', 'abc', 'bla'];
        $collection = new ReferencedCollection($array);
        $result = $collection->findClosest(null, 'abc');
        $this->assertSame('abc', $result[0]['entry']);
        $this->assertSame('bla', $result[1]['entry']);
        $this->assertSame('test', $result[2]['entry']);

    }

    public function testFindClosestOne()
    {
        $array = ['test', 'abc', 'bla'];
        $collection = new ReferencedCollection($array);
        $result = $collection->findClosestOne(null, 'abc');
        $this->assertSame('abc', $result);

    }


    public function testFindClosestOne_emptyList()
    {
        $array = [];
        $collection = new ReferencedCollection($array);
        $result = $collection->findClosestOne(null, 'abc');
        $this->assertSame(null, $result);

    }

    /**
     *
     */
    public function testFindIndex()
    {
        $array = ['test', 'abc', 'bla'];
        $collection = new ReferencedCollection($array);
        $result = $collection->findIndex(function ($value) {
            return ($value == 'abc');
        });
        $this->assertSame(1, $result);
    }


    /**
     *
     */
    public function testFindIndex_notFound()
    {
        $array = ['test', 'abc', 'bla'];
        $collection = new ReferencedCollection($array);
        $result = $collection->findIndex(function ($value) {
            return ($value == 'defg');
        });
        $this->assertFalse($result);
    }


    /**
     *
     */
    public function testFirst()
    {
        $array = [1, 2, 3];
        $collection = new ReferencedCollection($array);
        $this->assertSame(1, current($array));
        $this->assertSame(1, $collection->first());

        next($array);

        $this->assertSame(2, current($array));
        $this->assertSame(1, $collection->first());
        $this->assertSame(2, current($array));
    }


    /**
     *
     */
    public function testFlatten()
    {
        $collection = new Collection([
            'test' => 'value',
            'another' => [
                'subtest' => 'subvalue',
                'subsubarr' => [
                    1, 2, 3
                ]
            ]
        ]);

        /*
         expected result:
            array(3) {
              'test' =>
              string(5) "value"
              'subtest' =>
              string(8) "subvalue"
              'subsubarr' =>
              array(3) {
                [0] =>
                int(1)
                [1] =>
                int(2)
                [2] =>
                int(3)
              }
            }
         */


        $collection->flatten(1);
        $arr = $collection->getArray();
        $this->assertSame(3, count($arr));
        $this->assertSame('value', $arr['test']);
        $this->assertSame([1, 2, 3], $arr['subsubarr']);
        $this->assertSame('subvalue', $arr['subtest']);
    }


    /**
     *
     */
    public function testFlip()
    {
        $collection = new Collection(["A", "B", 'C', 'D', 'F']);
        $collection->flip();
        $arr = $collection->getArray();
        reset($arr);
        $this->assertSame(0, current($arr));
        next($arr);
        $this->assertSame(1, current($arr));
        next($arr);
        $this->assertSame(2, current($arr));
        next($arr);
        $this->assertSame(3, current($arr));
        next($arr);
        $this->assertSame(4, current($arr));
        next($arr);
    }

    /**
     *
     */
    public function testGet()
    {
        $collection = new Collection(['test', 'abc']);
        $keys = $collection->getKeys();
        $this->assertSame(2, $keys->count());
        $this->assertSame($keys->get(0), 0);
        $this->assertSame($keys->get(1), 1);
    }

    /**
     *
     */
    public function testGetIfExists()
    {
        $collection = new Collection(['exists' => true]);
        $this->assertSame(true, $collection->getIfExists("exists"));
        $this->assertSame(null, $collection->getIfExists("doesNotExist"));
        $this->assertSame("something", $collection->getIfExists("doesNotExist", "something"));
    }

    /**
     *
     */
    public function testGetKeys()
    {
        $collection = new Collection(['test', 'abc']);
        $keys = $collection->getKeys()->getArray();
        $this->assertSame(2, count($keys));
        $this->assertSame($keys[0], 0);
        $this->assertSame($keys[1], 1);
    }

    /**
     *
     */
    public function testGetValues()
    {
        $collection = new Collection(['test', 'abc']);
        $keys = $collection->getValues()->getArray();
        $this->assertSame(2, count($keys));
        $this->assertSame($keys[0], 'test');
        $this->assertSame($keys[1], 'abc');
    }

    /**
     *
     */
    public function testIndexOf()
    {
        $collection = new Collection(['test', 'abc']);
        $index = $collection->indexOf('abc');
        $this->assertSame(1, $index);
    }

    /**
     *
     */
    public function testInsert()
    {
        $collection = new Collection(['test']);
        $collection->insert("abc", "test");
        $this->assertSame('test', $collection->get("abc"));
    }

    /**
     *
     */
    public function testIsEmpty_notEmpty()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertFalse($collection->isEmpty());
    }

    /**
     *
     */
    public function testIsEmpty_isEmpty()
    {
        $collection = new Collection([]);
        $this->assertTrue($collection->isEmpty());
    }

    /**
     *
     */
    public function testJoin()
    {
        $collection = new Collection(['abc', 'defg', 'abc']);
        $result = $collection->join();
        $this->assertSame("abc,defg,abc", $result);

    }

    /**
     *
     */
    public function testJoin_withCustomSeparator()
    {
        $collection = new Collection(['abc', 'defg', 'abc']);
        $result = $collection->join("SEPARATOR");
        $this->assertSame("abcSEPARATORdefgSEPARATORabc", $result);
    }


    /**
     *
     */
    public function testLast()
    {
        $array = [1, 2, 3];
        $collection = new ReferencedCollection($array);
        $this->assertSame(1, current($array));
        $this->assertSame(3, $collection->last());

        next($array);

        $this->assertSame(2, current($array));
        $this->assertSame(3, $collection->last());
        $this->assertSame(2, current($array));
    }

    /**
     *
     */
    public function testLastIndexOf()
    {
        $collection = new Collection(['abc', 'defg', 'abc', 'defg']);
        $result = $collection->lastIndexOf('abc');
        $this->assertSame(2, $result);

        $result = $collection->lastIndexOf('defg');
        $this->assertSame(3, $result);
    }


    /**
     *
     */
    public function testKeyExists()
    {
        $collection = new Collection(['test' => 'bla']);
        $this->assertTrue($collection->keyExists('test'));
    }

    /**
     *
     */
    public function testKeyExists_doesNotExist()
    {
        $collection = new Collection(['test' => 'bla']);
        $this->assertFalse($collection->keyExists('undefined'));
    }

    /**
     *
     */
    public function testMap()
    {
        $collection = new Collection([
            [2, 2],
            [3, 3]
        ]);
        $collection->map(function ($value) {
            $result = 0;
            foreach ($value as $item) {
                $result += $item;
            }
            return $result;
        });
        $this->assertSame(4, $collection->get(0));
        $this->assertSame(6, $collection->get(1));
    }

    /**
     *
     */
    public function testMin()
    {
        $array = [5, 10, 15];
        $collection = new ReferencedCollection($array);
        $min = $collection->min();
        $this->assertSame(5.0, $min);
    }

    /**
     *
     */
    public function testMinSub()
    {
        $array = [
            ['test' => 50],
            ['test' => 15],
            ['nope' => 1],
            ['test' => 33]
        ];
        $collection = new ReferencedCollection($array);
        $min = $collection->minSub('test');
        $this->assertSame(15.0, $min);

        $min = $collection->minSub('nope');
        $this->assertSame(1.0, $min);
    }


    /**
     *
     */
    public function testMinCustom()
    {
        $array = [
            ['test' => 50],
            ['test' => 15],
            ['nope' => 1],
            ['test' => 33]
        ];
        $collection = new ReferencedCollection($array);
        $min = $collection->minCustom(function ($value) {
            return current($value);
        });
        $this->assertSame(1.0, $min);
    }

    /**
     *
     */
    public function testMinCustom_withFalseReturn()
    {
        $array = [
            ['test' => 50],
            ['test' => 15],
            ['nope' => false],
            ['test' => 33]
        ];
        $collection = new ReferencedCollection($array);
        $min = $collection->minCustom(function ($value) {
            return current($value);
        });
        $this->assertSame(15.0, $min);
    }


    /**
     *
     */
    public function testMax()
    {
        $array = [5, 10, 15];
        $collection = new ReferencedCollection($array);
        $max = $collection->max();
        $this->assertSame(15.0, $max);
    }

    /**
     *
     */
    public function testMaxChild()
    {
        $array = [
            ['test' => 50],
            ['test' => 15],
            ['nope' => 111],
            ['test' => 33]
        ];
        $collection = new ReferencedCollection($array);
        $max = $collection->maxChild('test');
        $this->assertSame(50.0, $max);

        $max = $collection->maxChild('nope');
        $this->assertSame(111.0, $max);
    }


    /**
     *
     */
    public function testMaxCustom()
    {
        $array = [
            ['test' => 50],
            ['test' => 15],
            ['nope' => 100],
            ['test' => 33]
        ];
        $collection = new ReferencedCollection($array);
        $max = $collection->maxCustom(function ($value) {
            return current($value);
        });
        $this->assertSame(100.0, $max);
    }


    /**
     *
     */
    public function testMaxCustom_withFalseReturn()
    {
        $array = [
            ['test' => 50],
            ['test' => 15],
            ['nope' => false],
            ['test' => 33]
        ];
        $collection = new ReferencedCollection($array);
        $max = $collection->maxCustom(function ($value) {
            return current($value);
        });
        $this->assertSame(50.0, $max);
    }

    /**
     *
     */
    public function testNth()
    {
        $array = [1, 2, 3, 4, 5, 6, 7, 8];
        $collection = new ReferencedCollection($array);
        $nth = $collection->nth(2);
        $this->assertSame(4, $nth->count());
        $this->assertSame(1, $nth[0]);
        $this->assertSame(3, $nth[1]);
        $this->assertSame(5, $nth[2]);
        $this->assertSame(7, $nth[3]);
    }


    /**
     *
     */
    public function testOnly()
    {
        $collection = new Collection([
            "a" => "one",
            "b" => "two",
            "c" => "three",
        ]);

        $only = $collection->only(['a', 'b']);
        $this->assertSame(2, $only->count());
        $this->assertSame('one', $only->get("a"));
        $this->assertSame('two', $only->get("b"));
        $this->assertFalse($only->keyExists("c"));
    }

    /**
     *
     */
    public function testPop()
    {
        $array = [1, 2, 3];
        $collection = new ReferencedCollection($array);
        $this->assertSame(3, $collection->pop());
        $this->assertSame(2, $collection->count());

        $this->assertSame(2, $collection->pop());
        $this->assertSame(1, $collection->count());

        $this->assertSame(1, $collection->pop());
        $this->assertSame(null, $collection->pop());
        $this->assertSame(0, $collection->count());
    }

    /**
     *
     */
    public function testPull()
    {
        $array = ['test' => 1, 'another' => 2];
        $collection = new ReferencedCollection($array);
        $entry = $collection->pull("test");
        $this->assertSame(1, $entry);
        $this->assertSame(1, count($array));
    }

    /**
     *
     */
    public function testPush()
    {
        $collection = new Collection([1, 2]);
        $this->assertSame(2, $collection->count());
        $collection->push("SomeTest");

        $this->assertTrue($collection->keyExists(2));
        $this->assertSame('SomeTest', $collection->get(2));
    }

    /**
     *
     */
    public function testPushOnTop()
    {
        $collection = new Collection([1, 2, 3]);
        $collection->pushOnTop('test');
        $this->assertSame("test", $collection->get(0));
        $this->assertSame(1, $collection->get(1));
        $this->assertSame(2, $collection->get(2));
        $this->assertSame(3, $collection->get(3));
    }

    public function testRemove()
    {
        $collection = new Collection([1, 2]);
        $collection->remove(0);
        $this->assertSame(1, $collection->count());
        $this->assertSame(2, $collection[1]);
    }


    public function testRemoveItem()
    {
        $testA = new \stdClass();
        $testB = new \stdClass();

        $collection = new Collection([$testA, $testB]);
        $collection->removeItem($testA);
        $this->assertSame(1, $collection->count());
        $this->assertSame($testB, $collection[1]);
    }

    /**
     *
     */
    public function testReverse()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $collection->reverse();

        $arr = $collection->getArray();
        $this->assertSame(5, current($arr));
        next($arr);
        $this->assertSame(4, current($arr));
        next($arr);
        $this->assertSame(3, current($arr));
        next($arr);
        $this->assertSame(2, current($arr));
        next($arr);
        $this->assertSame(1, current($arr));
        next($arr);
    }

    /**
     *
     */
    public function testSelect_noParameters()
    {
        $collection = new Collection(range(1, 20));
        $selection = $collection->select();
        $this->assertSame(20, count($selection));
    }

    /**
     *
     */
    public function testSelect_onlyLimit()
    {
        $collection = new Collection(range(1, 20));
        $selection = $collection->select(5);
        $this->assertSame(5, count($selection));
        $this->assertSame(1, $selection[0]);
        $this->assertSame(5, $selection[4]);
    }


    /**
     *
     */
    public function testSelect_onlyOffset()
    {
        $collection = new Collection(range(1, 20));
        $selection = $collection->select(null, 15);
        $this->assertSame(5, count($selection));
        $this->assertSame(16, $selection[0]);
        $this->assertSame(20, $selection[4]);
    }

    /**
     *
     */
    public function testSelect_limitAndOffset()
    {
        $collection = new Collection(range(1, 20));
        $selection = $collection->select(5, 5);
        $this->assertSame(5, count($selection));
        $this->assertSame(6, $selection[0]);
        $this->assertSame(10, $selection[4]);
    }

    /**
     *
     */
    public function testSelect_noParameters_collectionIntact()
    {
        $collection = new Collection(range(1, 20));
        $collection->select();
        $this->assertSame(20, $collection->count());

    }

    /**
     *
     */
    public function testShift()
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertSame(1, $collection->shift());
        $this->assertSame(2, $collection->shift());
        $this->assertSame(3, $collection->shift());
        $this->assertSame(null, $collection->shift());
        $this->assertSame(0, $collection->count());

    }

    /**
     *
     */
    public function testSplit()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $result = $collection->split(function ($value) {
            return $value % 2 == 0;
        });
        $this->assertSame(2, $result[0]->count());
        $this->assertSame(3, $result[1]->count());

        $this->assertSame(2, $result[0]->get(1));
        $this->assertSame(4, $result[0]->get(3));

        $this->assertSame(1, $result[1]->get(0));
        $this->assertSame(3, $result[1]->get(2));
        $this->assertSame(5, $result[1]->get(4));

    }

    /**
     *
     */
    public function testSortAlphabetically()
    {
        $collection = new Collection(['abc', 'some', 'another']);
        $collection->sortAlphabetically();
        self::assertSame('abc', $collection->get(0));
        self::assertSame('another', $collection->get(1));
        self::assertSame('some', $collection->get(2));
    }

    /**
     *
     */
    public function testSortAlphabeticallyKeepKeys()
    {
        $collection = new Collection(['abc', 'some', 'another']);
        $collection->sortAlphabeticallyKeepKeys();
        self::assertSame('abc', $collection->get(0));
        self::assertSame('another', $collection->get(2));
        self::assertSame('some', $collection->get(1));
    }


    /**
     *
     */
    public function testSortAlphabeticallyDec()
    {
        $collection = new Collection(['abc', 'some', 'another']);
        $collection->sortAlphabeticallyDesc();
        self::assertSame('some', $collection->get(0));
        self::assertSame('another', $collection->get(1));
        self::assertSame('abc', $collection->get(2));
    }

    /**
     *
     */
    public function testSortAlphabeticallyDescKeepKeys()
    {
        $collection = new Collection(['abc', 'some', 'another']);
        $collection->sortAlphabeticallyDescKeepKeys();
        self::assertSame('some', $collection->get(1));
        self::assertSame('another', $collection->get(2));
        self::assertSame('abc', $collection->get(0));
    }


    /**
     *
     */
    public function testSortCustom()
    {
        $collection = new Collection(['555', '666', '777']);
        $collection->sortCustom(function ($a, $b) {
            if ($a == "777") {
                return -1;
            } elseif ($b == "777") {
                return 1;
            }
            return 0;
        });
        self::assertSame('777', $collection->get(0));
        self::assertSame('555', $collection->get(1));
        self::assertSame('666', $collection->get(2));
    }

    /**
     *
     */
    public function testSortCustomKeepKeys()
    {
        $collection = new Collection(['555', '666', '777']);
        $collection->sortCustomKeepKeys(function ($a, $b) {
            if ($a == "777") {
                return -1;
            } elseif ($b == "777") {
                return 1;
            }
            return 0;
        });
        self::assertSame('777', $collection->get(2));
        self::assertSame('555', $collection->get(0));
        self::assertSame('666', $collection->get(1));
    }


    /**
     *
     */
    public function testSortKeysAlphabetical()
    {
        $collection = new Collection(['b' => 1, 'a' => 2, 'c' => 3]);
        $collection->sortKeysAlphabetical();
        $this->assertSame(2, reset($collection->getArrayReference()));
        $this->assertSame(1, next($collection->getArrayReference()));
        $this->assertSame(3, next($collection->getArrayReference()));
    }

    /**
     *
     */
    public function testSortKeysAlphabeticalDesc()
    {
        $collection = new Collection(['b' => 1, 'a' => 2, 'c' => 3]);
        $collection->sortKeysAlphabeticalDesc();
        $this->assertSame(3, reset($collection->getArrayReference()));
        $this->assertSame(1, next($collection->getArrayReference()));
        $this->assertSame(2, next($collection->getArrayReference()));
    }


    /**
     *
     */
    public function testSortKeysNumerical()
    {
        $collection = new Collection(['2' => "a", '1' => "b", '3' => "c"]);
        $collection->sortKeysNumerical();
        $this->assertSame("b", reset($collection->getArrayReference()));
        $this->assertSame("a", next($collection->getArrayReference()));
        $this->assertSame("c", next($collection->getArrayReference()));
    }

    /**
     *
     */
    public function testSortKeysNumericalDesc()
    {
        $collection = new Collection(['2' => "a", '1' => "b", '3' => "c"]);
        $collection->sortKeysNumericalDesc();
        $this->assertSame("c", reset($collection->getArrayReference()));
        $this->assertSame("a", next($collection->getArrayReference()));
        $this->assertSame("b", next($collection->getArrayReference()));
    }

    /**
     *
     */
    public function testSortKeysCustom()
    {
        $collection = new Collection(['2' => "a", '1' => "b", '3' => "c"]);
        $collection->sortKeysCustom(function ($a, $b) {
            if ($a == "2") {
                return -1;
            } elseif ($a == "1") {
                return 1;
            }
            return 0;
        });
        $this->assertSame("a", reset($collection->getArrayReference()));
        $this->assertSame("c", next($collection->getArrayReference()));
        $this->assertSame("b", next($collection->getArrayReference()));
    }


    /**
     *
     */
    public function testSortNumerical()
    {
        $collection = new Collection(['123', '3', '444', '15394']);
        $collection->sortNumerical();
        self::assertSame('3', $collection->get(0));
        self::assertSame('123', $collection->get(1));
        self::assertSame('444', $collection->get(2));
        self::assertSame('15394', $collection->get(3));
    }

    /**
     *
     */
    public function testSortNumericalKeepKeys()
    {
        $collection = new Collection(['123', '3', '444', '15394']);
        $collection->sortNumericalKeepKeys();
        self::assertSame('3', $collection->get(1));
        self::assertSame('123', $collection->get(0));
        self::assertSame('444', $collection->get(2));
        self::assertSame('15394', $collection->get(3));
    }


    /**
     *
     */
    public function testSortNumericallyDesc()
    {
        $collection = new Collection(['123', '3', '444', '15394']);
        $collection->sortNumericalDesc();
        self::assertSame('15394', $collection->get(0));
        self::assertSame('444', $collection->get(1));
        self::assertSame('123', $collection->get(2));
        self::assertSame('3', $collection->get(3));
    }


    /**
     *
     */
    public function testSortNumericallyDescKeepKeys()
    {
        $collection = new Collection(['123', '3', '444', '15394']);
        $collection->sortNumericalDescKeepKeys();
        self::assertSame('15394', $collection->get(3));
        self::assertSame('444', $collection->get(2));
        self::assertSame('123', $collection->get(0));
        self::assertSame('3', $collection->get(1));
    }

    /**
     *
     */
    public function testSum()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $sum = $collection->sum();
        $this->assertSame(15.0, $sum);

        $collection = new Collection([1, 2, 3, 4, 5, 14.5]);
        $sum = $collection->sum();
        $this->assertSame(29.5, $sum);
    }

    /**
     *
     */
    public function testSumInt()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $sum = $collection->sumInt();
        $this->assertSame(15, $sum);

        $collection = new Collection([1, 2, 3, 4, 5, 14.5]);
        $sum = $collection->sumInt();
        $this->assertSame(29, $sum);
    }

    /**
     *
     */
    public function testTestAny()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $result = $collection->testAny(function ($value) {
            return ($value == 3);
        });
        $this->assertTrue($result);
    }

    /**
     *
     */
    public function testTestAny_fail()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $result = $collection->testAny(function ($value) {
            return ($value == 15);
        });
        $this->assertFalse($result);
    }

    /**
     *
     */
    public function testTestAll()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $result = $collection->testAll(function () {
            return true;
        });
        $this->assertTrue($result);
    }

    /**
     *
     */
    public function testTestAll_fail()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $result = $collection->testAll(function ($value) {
            return ($value != 3);
        });
        $this->assertFalse($result);
    }

    /**
     *
     */
    public function testUnique()
    {
        $collection = new Collection([1, 1, 1, 1, 1, 2, 2, 2, 3]);
        $result = $collection->unique();
        $this->assertSame(3, $result->count());
    }

    /**
     *
     */
    public function testUniqueByKey()
    {
        $collection = new Collection([
            [
                'test' => 'abc'
            ],
            [
                'test' => 'abc'
            ],
            [
                'test' => 'abc'
            ],
            [
                'test' => 'abc'
            ],
            [
                'test' => 'defg'
            ]
        ]);
        $result = $collection->uniqueByKey('test');
        $this->assertSame(2, $result->count());
        $this->assertSame("abc", $result[0]);
        $this->assertSame("defg", $result[4]);
    }

    /**
     *
     */
    public function testUnshift()
    {
        $collection = new Collection([1, 2, 3]);
        $collection->unshift('test');
        $this->assertSame("test", $collection->get(0));
        $this->assertSame(1, $collection->get(1));
        $this->assertSame(2, $collection->get(2));
        $this->assertSame(3, $collection->get(3));
    }


    /**
     *
     */
    public function testValueExists_fail()
    {
        $collection = new Collection(["test", "another"]);
        $this->assertFalse($collection->contains('undefined'));
    }


    /**
     *
     */
    public function testArrayAccess_exists()
    {
        $array = ['key' => 'test'];
        $collection = new ReferencedCollection($array);
        $this->assertTrue(isset($collection['key']));
        $this->assertFalse(isset($collection['undefined']));
    }

    /**
     *
     */
    public function testArrayAccess_get()
    {
        $array = ['key' => 'test'];
        $collection = new ReferencedCollection($array);
        $this->assertSame('test', $collection['key']);
    }


    /**
     *
     */
    public function testArrayAccess_set()
    {
        $array = ['key' => 'test'];
        $collection = new ReferencedCollection($array);
        $this->assertSame('test', $array['key']);
        $collection['key'] = 'test2';
        $this->assertSame('test2', $array['key']);
    }

    /**
     *
     */
    public function testArrayAccess_unset()
    {
        $array = ['key' => 'test'];
        $collection = new ReferencedCollection($array);
        unset($collection['key']);
        $this->assertSame(0, count($array));
    }

    /**
     *
     */
    public function testCountable_count()
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertSame(5, count($collection));
    }

    /**
     *
     */
    public function testIterator_foreach()
    {
        $array = ['key' => 'test', 'key2' => 'test2'];
        $collection = new ReferencedCollection($array);
        $count = 0;
        foreach ($collection as $key => $value) {
            switch ($count) {
                case 0:
                    $this->assertSame("key", $key);
                    $this->assertSame("test", $value);
                    break;
                case 1:
                    $this->assertSame("key2", $key);
                    $this->assertSame("test2", $value);
                    break;
                default:
                    $this->fail("Should not have happened");
            }
            ++$count;
        }
        $this->assertSame(2, $count);

    }


}