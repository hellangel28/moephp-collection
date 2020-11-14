<?php


namespace MoePHP4\Collections;


/**
 * Class Collection
 * @package MoePHP4\Collections
 */
class Collection extends ReferencedCollection
{
    /**
     * Collection constructor.
     * @param array $array
     */
    public function __construct(array $array = [])
    {
        parent::__construct($array);
    }

}