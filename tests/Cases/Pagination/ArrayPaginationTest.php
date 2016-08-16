<?php

namespace Phact\Tests;

use Phact\Pagination\Pagination;

class ArrayPaginationTest extends AppTest
{
    public function testGetArray()
    {
        return [1,2,3,4,5,6,7,8];
    }

    /**
     * @depends testGetArray
     * @param $array array
     */
    public function testPageSize($array)
    {
        $pagination = new Pagination($array, [
            'pageSize' => 3,
            'redirectInvalidPage' => false
        ]);
        $firstData = $pagination->data;
        $this->assertEquals([1,2,3], $firstData);

        $pagination = new Pagination($array, [
            'pageSize' => 4,
            'redirectInvalidPage' => false
        ]);
        $firstData = $pagination->data;
        $this->assertEquals([1,2,3,4], $firstData);
    }

    /**
     * @depends testGetArray
     * @param $array array
     */
    public function testPage($array)
    {
        $pagination = new Pagination($array, [
            'pageSize' => 3,
            'page' => 2,
            'redirectInvalidPage' => false
        ]);
        $firstData = $pagination->data;
        $this->assertEquals([4,5,6], $firstData);
    }

    /**
     * @depends testGetArray
     * @param $array array
     */
    public function testPages($array)
    {
        $pagination = new Pagination($array, [
            'pageSize' => 3,
            'redirectInvalidPage' => false
        ]);
        $this->assertEquals(3, $pagination->getLastPage());
        $this->assertEquals(false, $pagination->hasPreviousPage());
        $this->assertEquals(true, $pagination->hasNextPage());

        $pagination = new Pagination($array, [
            'pageSize' => 3,
            'page' => 2,
            'redirectInvalidPage' => false
        ]);
        $this->assertEquals(3, $pagination->getLastPage());
        $this->assertEquals(true, $pagination->hasPreviousPage());
        $this->assertEquals(true, $pagination->hasNextPage());

        $pagination = new Pagination($array, [
            'pageSize' => 3,
            'page' => 3,
            'redirectInvalidPage' => false
        ]);
        $this->assertEquals(3, $pagination->getLastPage());
        $this->assertEquals(true, $pagination->hasPreviousPage());
        $this->assertEquals(false, $pagination->hasNextPage());
    }
}