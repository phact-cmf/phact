<?php

namespace Phact\Tests;


use Modules\Test\Models\Company;
use Phact\Orm\QuerySet;
use Phact\Pagination\Pagination;

class QuerySetPaginationTest extends DatabaseTest
{
    public function useModels()
    {
        return [new Company];
    }

    public function getQs()
    {
        $data = [
            'Apple' => 1990,
            'Amazon' => 1992,
            'Intel' => 1993,
            'IBM' => 1996,
            'Facebook' => 2004,
            'Coca-cola' => 1930,
            'P and G' => 1940,
            'Yahoo' => 1980
        ];

        foreach ($data as $name => $year) {
            $company = new Company();
            $company->name = $name;
            $company->founded = $year;
            $company->save();
        }

        return Company::objects()->getQuerySet();
    }

    public function testPageSize()
    {
        $qs = $this->getQs();
        $pagination = new Pagination($qs, [
            'pageSize' => 3,
            'redirectInvalidPage' => false,
            'dataType' => 'raw'
        ]);
        $firstData = $pagination->data;
        $this->assertEquals(3, count($firstData));

        $pagination = new Pagination($qs, [
            'pageSize' => 4,
            'redirectInvalidPage' => false,
            'dataType' => 'raw'
        ]);
        $firstData = $pagination->data;
        $this->assertEquals(4, count($firstData));
    }

    public function testPage()
    {
        $qs = $this->getQs();
        $pagination = new Pagination($qs, [
            'pageSize' => 3,
            'page' => 2,
            'redirectInvalidPage' => false,
            'dataType' => 'raw'
        ]);
        $firstData = $pagination->data;
        $this->assertEquals([
            [
                'id' => '4',
                'name' => 'IBM',
                'founded' => '1996'
            ],
            [
                'id' => '5',
                'name' => 'Facebook',
                'founded' => '2004'
            ],
            [
                'id' => '6',
                'name' => 'Coca-cola',
                'founded' => '1930'
            ]
        ], $firstData);
    }

    public function testPages()
    {
        $qs = $this->getQs();
        $pagination = new Pagination($qs, [
            'pageSize' => 3,
            'redirectInvalidPage' => false
        ]);
        $this->assertEquals(3, $pagination->getLastPage());
        $this->assertEquals(false, $pagination->hasPreviousPage());
        $this->assertEquals(true, $pagination->hasNextPage());

        $pagination = new Pagination($qs, [
            'pageSize' => 3,
            'page' => 2,
            'redirectInvalidPage' => false
        ]);
        $this->assertEquals(3, $pagination->getLastPage());
        $this->assertEquals(true, $pagination->hasPreviousPage());
        $this->assertEquals(true, $pagination->hasNextPage());

        $pagination = new Pagination($qs, [
            'pageSize' => 3,
            'page' => 3,
            'redirectInvalidPage' => false
        ]);
        $this->assertEquals(3, $pagination->getLastPage());
        $this->assertEquals(true, $pagination->hasPreviousPage());
        $this->assertEquals(false, $pagination->hasNextPage());
    }
}