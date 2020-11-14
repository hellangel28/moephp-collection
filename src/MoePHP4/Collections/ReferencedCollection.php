<?php


namespace MoePHP4\Collections;


use ArrayAccess;
use Closure;
use Countable;
use Iterator;
use MoePHP4\Collections\Exception\CollectionException;

/**
 * @todo: implement pluck($pluckKey, $resultKey=null, $startDepth=0, $endDepth=null=unlimited) => searches the array for $resultKey and adds it to the result (either push, or insert with the $foundInArray[$resultKey] as key if $resultKey is set)
 * Class Collections
 * @package MoePHP4\Collections
 */
class ReferencedCollection implements ArrayAccess, Iterator, Countable
{

    /**
     * If collection, returns the unwrapped array from a collection, if array returns the array, otherwise throws an exception
     * Note: This will return a reference of the underlying array!
     * @param $arrayOrCollection
     * @return array
     * @throws CollectionException
     */
    public static function &unwrap($arrayOrCollection): array
    {
        if (is_array($arrayOrCollection)) {
            return $arrayOrCollection;
        }
        if ($arrayOrCollection instanceof ReferencedCollection) {
            return $arrayOrCollection->getArrayReference();
        }
        throw new CollectionException("Can only unwrap collections and arrays");
    }

    /**
     * @var array
     */
    private $array;

    /**
     * Collections constructor.
     * Returns a collection with the reference of the given array under it
     * @param array $array
     */
    public function __construct(array &$array = [])
    {
        $this->array = &$array;
    }

    /**
     * Will iterate through the array and call the reducer callback for every entry, overwriting the current accumulator with the new return value of the reducer and returns the last one
     * First callback parameter: current accumulator (not a reference)
     * Second callback parameter: the current iterations value
     * Third callback parameter: the current iterations key
     * Fourth callback parameter: the this collection
     * @param Closure $reducer
     * @return float
     */
    public function accumulate(Closure $reducer): float
    {
        $accumulator = 0;
        foreach ($this->array as $key => $value) {
            $accumulator = call_user_func($reducer, $accumulator, $value, $key, $this);
        }
        return $accumulator;
    }


    /**
     * Returns the average of the collection
     * @return float
     */
    public function average(): float
    {
        return $this->sum() / $this->count();
    }

    /**
     * @param int $chunkSize
     * @return Collection
     */
    public function chunk(int $chunkSize): Collection
    {
        $result = new Collection();
        foreach (array_chunk($this->array, $chunkSize, true) as $chunk) {
            $result->push(new Collection($chunk));
        }
        return $result;
    }

    /**
     * @return $this
     */
    public function clear(): self
    {
        $this->array = [];
        return $this;
    }

    /**
     * @param array $array
     * @param bool $overwriteKeys
     * @return $this
     */
    public function concat(array $array, bool $overwriteKeys = true): self
    {
        foreach ($array as $key => $value) {
            if (!$overwriteKeys && $this->keyExists($key)) {
                continue;
            }
            $this->insert($key, $value);
        }
        return $this;
    }

    /**
     * @param ReferencedCollection $collection
     * @param bool $overwriteKeys
     * @return $this
     */
    public function concatCollection(ReferencedCollection $collection, bool $overwriteKeys = true): self
    {
        return $this->concat($collection->getArray(), $overwriteKeys);
    }

    /**
     * @param $value
     * @return bool
     */
    public function contains($value): bool
    {
        return $this->indexOf($value) !== false;
    }


    /**
     * Copies the contents to new collection and returns it
     * @return Collection
     */
    public function copy(): Collection
    {
        return new Collection($this->array);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->array);
    }


    /**
     * @param array $keys
     * @return Collection
     */
    public function extract(array $keys): Collection
    {
        $result = new Collection();
        foreach ($keys as $key) {
            if ($this->keyExists($key)) {
                $exists = $this->keyExists($key);
                $result->insert($key, $exists ? $this->get($key) : null);
                if ($exists) {
                    $this->remove($key);
                }
            }
        }
        return $result;
    }

    /**
     * @param Closure $condition
     * @return $this
     */
    public function filter(Closure $condition): self
    {
        foreach ($this->array as $key => $value) {
            if (!call_user_func($condition, $value, $key, $this->array)) {
                unset($this->array[$key]);
            }
        }
        return $this;
    }

    /**
     * @param Closure $condition
     * @return false|mixed
     */
    public function find(Closure $condition)
    {
        foreach ($this->array as $key => $value) {
            if (call_user_func($condition, $value, $key, $this->array)) {
                return $value;
            }
        }
        return false;
    }


    /**
     * @param string|null $key Key of each data entry to use for checks - if null, uses data itself for comparision
     * @param string $value Value to check against
     * @param int $maxDifference Maximum difference
     * @return Collection
     */
    public function findClosest(?string $key, string $value, ?int $maxDifference = -1): Collection
    {
        $found = new Collection();
        foreach ($this->array as $entry) {
            /**
             * @var string $fieldValue
             */
            $fieldValue = ($key !== null ? $entry[$key] : $entry);
            $score = levenshtein($value, $fieldValue);
            if ($maxDifference === -1 || $score <= $maxDifference) {
                $found[$score . "_" . uniqid()] = ['score' => $score, 'entry' => $entry];
            }
        }
        return $found->sortByField('score')->getValues();
    }


    /**
     * @param string|null $key Key of each data entry to use for checks
     * @param string $value Value to check against
     * @param int $maxDifference Maximum difference
     * @return string|null
     * @todo: add test with empty array
     */
    public function findClosestOne(?string $key, string $value, $maxDifference = null): ?string
    {
        $data = $this->findClosest($key, $value, ($maxDifference === null ? -1 : $maxDifference));
        $first = $data->first();
        if (!$first) {
            return null;
        }
        return $first['entry'];
    }

    /**
     * @param Closure $condition
     * @return false|mixed
     */
    public function findIndex(Closure $condition)
    {
        foreach ($this->array as $key => $value) {
            if (call_user_func($condition, $value, $key, $this->array)) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Returns the first element of the array
     * @return mixed
     */
    public function first()
    {
        $copy = $this->array;
        return reset($copy);
    }

    /**
     * @param int $depth
     * @return $this
     * @todo: write tests for sub arrays getting flattened
     */
    public function flatten(int $depth = 1): self
    {
        $this->_flatten($this->array, $depth);
        return $this;
    }

    /**
     * @param $array
     * @param int $depth
     */
    private function _flatten(&$array, int $depth): void
    {
        $copy = $array;
        foreach ($copy as $key => $value) {
            if (is_array($value) && $depth > 1) {
                $this->_flatten($array[$key], $depth - 1);
            } elseif ($depth > 0 && is_array($value)) {
                foreach ($value as $valKey => $valValue) {
                    $array[$valKey] = $valValue;
                }
                unset($array[$key]);
            }
        }
    }


    /**
     * @return $this
     */
    public function flip(): self
    {
        $this->array = array_flip($this->array);
        return $this;
    }

    /**
     * @param string|integer|double $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->array[$key];
    }

    /**
     * Returns a copy of the underlying array
     * @return array
     */
    public function getArray(): array
    {
        return $this->array;
    }

    /**
     * Returns a reference to the underlying array
     * @return array
     */
    public function &getArrayReference()
    {
        return $this->array;
    }

    /**
     * @param string|integer|double $key
     * @param null $fallback
     * @return mixed|null
     */
    public function getIfExists($key, $fallback = null)
    {
        return $this->keyExists($key) ? $this->get($key) : $fallback;
    }

    /**
     * @return Collection
     */
    public function getKeys(): Collection
    {
        return new Collection(array_keys($this->array));
    }

    /**
     * @return Collection
     */
    public function getValues(): Collection
    {
        return new Collection(array_values($this->array));
    }

    /**
     * @param $value
     * @param bool $strict
     * @return false|int|string
     */
    public function indexOf($value, bool $strict = true)
    {
        return array_search($value, $this->array, $strict);
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function insert($key, $value): self
    {
        $this->array[$key] = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @param string $separator
     * @return string
     */
    public function join($separator = ","): string
    {
        return implode($separator, $this->array);
    }

    /**
     * Returns the last entry of the array
     * @return mixed
     */
    public function last()
    {
        $copy = $this->array;
        return end($copy);
    }

    /**
     * @param $value
     * @param bool $strict
     * @return false|int|string
     */
    public function lastIndexOf($value, bool $strict = true)
    {
        $index = false;
        foreach ($this->array as $key => $arrayValue) {
            if (($strict && $value === $arrayValue) || (!$strict && $value == $arrayValue)) {
                $index = $key;
            }
        }
        return $index;
    }

    /**
     * @param $key
     * @return bool
     */
    public function keyExists($key): bool
    {
        return array_key_exists($key, $this->array);
    }

    /**
     * @param Closure $mapping
     * @return $this
     */
    public function map(Closure $mapping): self
    {
        foreach ($this->array as $key => &$value) {
            $value = call_user_func($mapping, $value, $key, $this->array);
        }
        return $this;
    }


    /**
     * @return float
     */
    public function min(): float
    {
        return $this->minCustom(function ($item) {
            return $item;
        });
    }

    /**
     * @param string|integer|float $subArrayKey
     * @return float
     */
    public function minSub($subArrayKey): float
    {
        return $this->minCustom(function ($item) use ($subArrayKey) {
            return isset($item[$subArrayKey]) ? $item[$subArrayKey] : false;
        });
    }

    /**
     * @param Closure $closure
     * @return float
     */
    public function minCustom(Closure $closure): float
    {
        $min = null;
        foreach ($this->array as $key => $item) {
            $value = call_user_func($closure, $item, $key, $this);
            if ($value === false) {
                continue;
            }
            $min = $min === null ? $value : min($min, $value);
        }
        return $min;
    }


    /**
     * @return float
     */
    public function max(): float
    {
        return $this->maxCustom(function ($item) {
            return $item;
        });
    }

    /**
     * @param string|integer|float $subArrayKey
     * @return float
     */
    public function maxChild($subArrayKey): float
    {
        return $this->maxCustom(function ($item) use ($subArrayKey) {
            return isset($item[$subArrayKey]) ? $item[$subArrayKey] : false;
        });
    }

    /**
     * @param Closure $closure
     * @return float|mixed
     */
    public function maxCustom(Closure $closure): float
    {
        $max = null;
        foreach ($this->array as $key => $item) {
            $value = call_user_func($closure, $item, $key, $this);
            if ($value === false) {
                continue;
            }
            $max = $max === null ? $value : max($max, $value);
        }
        return $max;
    }

    /**
     * Returns every nth items in a new collection
     * @param int $step
     * @param bool $keepKeys
     * @return ReferencedCollection
     */
    public function nth(int $step, bool $keepKeys = false): ReferencedCollection
    {
        $result = new ReferencedCollection();
        $this->withNth($step, function ($item, $key) use ($result, $keepKeys) {
            if ($keepKeys) {
                $result->insert($key, $item);
            } else {
                $result->push($item);
            }
        });
        return $result;
    }


    /**
     * Returns a new Collections containing all entries having a key contained in $keys
     * @param array $keys
     * @return ReferencedCollection
     */
    public function only(array $keys): ReferencedCollection
    {
        $result = new ReferencedCollection();
        foreach ($keys as $key) {
            if ($this->keyExists($key)) {
                $result->insert($key, $this->keyExists($key) ? $this->get($key) : null);
            }
        }
        return $result;
    }

    /**
     * Pop the element off the end of array
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->array);
    }

    /**
     * @param string|integer|float $index
     * @return mixed
     */
    public function pull($index)
    {
        $value = $this[$index];
        unset($this[$index]);
        return $value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function push($value): self
    {
        $this->array[] = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function pushOnTop($value): self
    {
        array_unshift($this->array, $value);
        return $this;
    }

    /**
     * @param $index
     * @return $this
     */
    public function remove($index): self
    {
        unset($this->array[$index]);
        return $this;
    }

    /**
     * @param $item
     * @return bool
     */
    public function removeItem($item): bool
    {
        $index = $this->indexOf($item);
        if ($index === false) {
            return false;
        }
        $this->remove($index);
        return true;
    }

    /**
     * @return $this
     */
    public function reverse(): self
    {
        $this->array = array_reverse($this->array);
        return $this;
    }

    /**
     * Returns a selection of the array
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function select(?int $limit = null, ?int $offset = null): array
    {
        $copy = $this->array;
        if ($limit !== null && $offset !== null) {
            return array_splice($copy, $offset, $limit);
        } elseif ($offset !== null) {
            return array_splice($copy, $offset);
        } elseif ($limit !== null) {
            return array_splice($copy, 0, $limit);
        }
        return $copy;

    }

    /**
     * Shift an element off the beginning of array
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->array);
    }

    /**
     * @param string $fieldName
     * @param bool $ascending
     * @return $this
     */
    public function sortByField(string $fieldName, $ascending = true): self
    {
        return $this->sortByFields([$fieldName => ($ascending ? "asc" : "desc")]);
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function sortByFields(array $fields): self
    {
        return $this->sortCustomKeepKeys(function ($a, $b) use ($fields) {
            foreach ($fields as $key => $value) {
                if ($a[$key] > $b[$key]) {
                    return (strtolower($value) == 'asc') ? 1 : -1;
                }
                if ($a[$key] < $b[$key]) {
                    return (strtolower($value) == 'asc') ? -1 : 1;
                }
            }
            return 0;
        });
    }


    /**
     * Splits the collection into 2 collection, deciding with the condition closure to which collection the entry is added (left or right)
     * @param Closure $condition If the condition returns true, the entry will be added to the left side, otherwise to the right side
     * @param string $leftKey
     * @param string $rightKey
     * @return Collection
     */
    public function split(Closure $condition, string $leftKey = "0", string $rightKey = "1"): Collection
    {
        $left = new ReferencedCollection();
        $right = new ReferencedCollection();
        foreach ($this->array as $key => $value) {
            $toLeft = call_user_func($condition, $value, $key, $this);
            if ($toLeft) {
                $left->insert($key, $value);
            } else {
                $right->insert($key, $value);
            }
        }
        return new Collection([$leftKey => $left, $rightKey => $right]);
    }

    /**
     * @return $this
     */
    public function sortAlphabetically(): self
    {
        sort($this->array);
        return $this;

    }

    /**
     * @return $this
     */
    public function sortAlphabeticallyKeepKeys(): self
    {
        asort($this->array);
        return $this;
    }

    /**
     * @return $this
     */
    public function sortAlphabeticallyDesc(): self
    {
        rsort($this->array);
        return $this;
    }

    /**
     * @return $this
     */
    public function sortAlphabeticallyDescKeepKeys(): self
    {
        arsort($this->array);
        return $this;
    }

    /**
     * @param Closure $algorithm
     * @return $this
     */
    public function sortCustom(Closure $algorithm): self
    {
        usort($this->array, $algorithm);
        return $this;
    }

    /**
     * @param Closure $algorithm
     * @return $this
     */
    public function sortCustomKeepKeys(Closure $algorithm): self
    {
        uasort($this->array, $algorithm);
        return $this;
    }


    /**
     * @return $this
     */
    public function sortKeysAlphabetical(): self
    {
        ksort($this->array);
        return $this;
    }

    /**
     * @return $this
     */
    public function sortKeysAlphabeticalDesc(): self
    {
        krsort($this->array);
        return $this;
    }

    /**
     * @param Closure $algorithm
     * @return $this
     */
    public function sortKeysCustom(Closure $algorithm): self
    {
        uksort($this->array, $algorithm);
        return $this;
    }


    /**
     * @return $this
     */
    public function sortKeysNumerical(): self
    {
        ksort($this->array, SORT_NUMERIC);
        return $this;
    }

    /**
     * @return $this
     */
    public function sortKeysNumericalDesc(): self
    {
        krsort($this->array, SORT_NUMERIC);
        return $this;
    }

    /**
     * @return $this
     */
    public function sortNumerical(): self
    {
        sort($this->array, SORT_NUMERIC);
        return $this;
    }

    /**
     * @return $this
     */
    public function sortNumericalKeepKeys(): self
    {
        asort($this->array, SORT_NUMERIC);
        return $this;
    }

    /**
     * @return $this
     */
    public function sortNumericalDesc(): self
    {
        rsort($this->array, SORT_NUMERIC);
        return $this;
    }


    /**
     * @return $this
     */
    public function sortNumericalDescKeepKeys(): self
    {
        arsort($this->array, SORT_NUMERIC);
        return $this;
    }


    /**
     * @return float
     */
    public function sum(): float
    {
        return $this->accumulate(function ($accumulator, $value) {
            return $accumulator + (float)$value;
        });
    }

    /**
     * @return int
     */
    public function sumInt(): int
    {
        return $this->sum();
    }

    /**
     * @param Closure $test
     * @return bool
     */
    public function testAny(Closure $test): bool
    {
        foreach ($this->array as $key => $value) {
            if (call_user_func($test, $value, $key, $this->array)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Closure $test
     * @return bool
     */
    public function testAll(Closure $test): bool
    {
        foreach ($this->array as $key => $value) {
            if (!call_user_func($test, $value, $key, $this->array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns a new collection with all unique values
     * @param int $sortFlags
     * @return Collection
     */
    public function unique($sortFlags = SORT_REGULAR): Collection
    {
        return new Collection(array_unique($this->array, $sortFlags));
    }

    /**
     * @param $key
     * @param int $sortFlags
     * @return Collection
     */
    public function uniqueByKey($key, $sortFlags = SORT_REGULAR): Collection
    {
        $data = new ReferencedCollection();
        foreach ($this as $aValue) {
            if (is_array($aValue) && array_key_exists($key, $aValue)) {
                $data->push($aValue[$key]);
            }
        }
        return $data->unique($sortFlags);
    }

    /**
     * @param $value
     * @return $this
     */
    public function unshift($value): self
    {
        return $this->pushOnTop($value);
    }

    /**
     * Executes the closure for every nth entry in the array
     * @param int $step
     * @param Closure $closure
     */
    public function withNth(int $step, Closure $closure): void
    {
        $c = 0;
        foreach ($this->array as $key => $item) {
            if ($c % $step === 0) {
                call_user_func($closure, $item, $key, $c);
            }
            ++$c;
        }
    }


    #region ArrayAccess implementation

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->array);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->array[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value): void
    {
        $this->array[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset): void
    {
        unset($this->array[$offset]);
    }
    #endregion

    #region Iterator implementation
    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->array);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->array);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->array);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid(): bool
    {
        return $this->current() !== false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind(): void
    {
        reset($this->array);
    }
    #endregion
}