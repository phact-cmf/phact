<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/04/16 10:14
 */

namespace Phact\Tests\Cases\Orm\Abs;

use Modules\Test\Models\Author;
use Modules\Test\Models\Book;
use Modules\Test\Models\BookCategory;
use Modules\Test\Models\Group;
use Modules\Test\Models\Membership;
use Modules\Test\Models\Note;
use Modules\Test\Models\NoteThesis;
use Phact\Orm\Fields\HasManyField;
use Phact\Tests\Templates\DatabaseTest;

abstract class AbstractTreeTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new BookCategory()
        ];
    }

    public function testCreateRoot()
    {
        $category = new BookCategory();
        $category->name = "Thrillers";
        $category->save();

        $this->assertEquals(
            [[
                'id' => '1',
                'parent_id' => null,
                'lft' => '1',
                'rgt' => '2',
                'root' => '1',
                'depth' => '1',
                'name' => 'Thrillers'
            ]],
            BookCategory::objects()->values()
        );
    }

    public function testCreateRoots()
    {
        $fantasy = new BookCategory();
        $fantasy->name = "Fantasy";
        $fantasy->save();

        $poetry = new BookCategory();
        $poetry->name = "Poetry";
        $poetry->save();

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '2',
                    'root' => '1',
                    'depth' => '1',
                    'name' => 'Fantasy'
                ],
                [
                    'id' => '2',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '2',
                    'root' => '2',
                    'depth' => '1',
                    'name' => 'Poetry'
                ]
            ],
            BookCategory::objects()->values()
        );
    }

    public function testCreateRootAndChild()
    {
        $fantasy = new BookCategory();
        $fantasy->name = "Fantasy";
        $fantasy->save();

        $science = new BookCategory();
        $science->name = "Science fiction";
        $science->parent = $fantasy;
        $science->save();

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '4',
                    'root' => '1',
                    'depth' => '1',
                    'name' => 'Fantasy'
                ],
                [
                    'id' => '2',
                    'parent_id' => '1',
                    'lft' => '2',
                    'rgt' => '3',
                    'root' => '1',
                    'depth' => '2',
                    'name' => 'Science fiction'
                ]
            ],
            BookCategory::objects()->values()
        );
    }

    public function testCreateRootAndChilds()
    {
        $fantasy = new BookCategory();
        $fantasy->name = "Fantasy";
        $fantasy->save();

        $science = new BookCategory();
        $science->name = "Science fiction";
        $science->parent = $fantasy;
        $science->save();

        $mystic = new BookCategory();
        $mystic->name = "Mystic";
        $mystic->parent = $fantasy;
        $mystic->save();

        $earth = new BookCategory();
        $earth->name = "Science fiction of Earth";
        $earth->parent = $science;
        $earth->save();

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '8',
                    'root' => '1',
                    'depth' => '1',
                    'name' => 'Fantasy'
                ],
                [
                    'id' => '2',
                    'parent_id' => '1',
                    'lft' => '2',
                    'rgt' => '5',
                    'root' => '1',
                    'depth' => '2',
                    'name' => 'Science fiction'
                ],
                [
                    'id' => '4',
                    'parent_id' => '2',
                    'lft' => '3',
                    'rgt' => '4',
                    'root' => '1',
                    'depth' => '3',
                    'name' => 'Science fiction of Earth'
                ],
                [
                    'id' => '3',
                    'parent_id' => '1',
                    'lft' => '6',
                    'rgt' => '7',
                    'root' => '1',
                    'depth' => '2',
                    'name' => 'Mystic'
                ],
            ],
            BookCategory::objects()->values()
        );
    }

    public function testFullRootsAndChilds()
    {
        $fantasy = new BookCategory();
        $fantasy->name = "Fantasy";
        $fantasy->save();

        $science = new BookCategory();
        $science->name = "Science fiction";
        $science->parent = $fantasy;
        $science->save();

        $mystic = new BookCategory();
        $mystic->name = "Mystic";
        $mystic->parent = $fantasy;
        $mystic->save();

        $earth = new BookCategory();
        $earth->name = "Science fiction of Earth";
        $earth->parent = $science;
        $earth->save();

        $thrillers = new BookCategory();
        $thrillers->name = "Thrillers";
        $thrillers->save();

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '8',
                    'root' => '1',
                    'depth' => '1',
                    'name' => 'Fantasy'
                ],
                [
                    'id' => '2',
                    'parent_id' => '1',
                    'lft' => '2',
                    'rgt' => '5',
                    'root' => '1',
                    'depth' => '2',
                    'name' => 'Science fiction'
                ],
                [
                    'id' => '4',
                    'parent_id' => '2',
                    'lft' => '3',
                    'rgt' => '4',
                    'root' => '1',
                    'depth' => '3',
                    'name' => 'Science fiction of Earth'
                ],
                [
                    'id' => '3',
                    'parent_id' => '1',
                    'lft' => '6',
                    'rgt' => '7',
                    'root' => '1',
                    'depth' => '2',
                    'name' => 'Mystic'
                ],
                [
                    'id' => '5',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '2',
                    'root' => '2',
                    'depth' => '1',
                    'name' => 'Thrillers'
                ],
            ],
            BookCategory::objects()->values()
        );

        $mystic->fetchTreePosition();
        $mystic->parent = $thrillers;
        $mystic->save();

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '6',
                    'root' => '1',
                    'depth' => '1',
                    'name' => 'Fantasy'
                ],
                [
                    'id' => '2',
                    'parent_id' => '1',
                    'lft' => '2',
                    'rgt' => '5',
                    'root' => '1',
                    'depth' => '2',
                    'name' => 'Science fiction'
                ],
                [
                    'id' => '4',
                    'parent_id' => '2',
                    'lft' => '3',
                    'rgt' => '4',
                    'root' => '1',
                    'depth' => '3',
                    'name' => 'Science fiction of Earth'
                ],
                [
                    'id' => '5',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '4',
                    'root' => '2',
                    'depth' => '1',
                    'name' => 'Thrillers'
                ],
                [
                    'id' => '3',
                    'parent_id' => '5',
                    'lft' => '2',
                    'rgt' => '3',
                    'root' => '2',
                    'depth' => '2',
                    'name' => 'Mystic'
                ],
            ],
            BookCategory::objects()->values()
        );

        $thrillers->fetchTreePosition();
        $thrillers->delete();

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '6',
                    'root' => '1',
                    'depth' => '1',
                    'name' => 'Fantasy'
                ],
                [
                    'id' => '2',
                    'parent_id' => '1',
                    'lft' => '2',
                    'rgt' => '5',
                    'root' => '1',
                    'depth' => '2',
                    'name' => 'Science fiction'
                ],
                [
                    'id' => '4',
                    'parent_id' => '2',
                    'lft' => '3',
                    'rgt' => '4',
                    'root' => '1',
                    'depth' => '3',
                    'name' => 'Science fiction of Earth'
                ]
            ],
            BookCategory::objects()->values()
        );

        $science->fetchTreePosition();
        $science->parent = null;
        $science->save();

        $this->assertEquals(
            [
                [
                    'id' => '1',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '2',
                    'root' => '1',
                    'depth' => '1',
                    'name' => 'Fantasy'
                ],
                [
                    'id' => '2',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '4',
                    'root' => '2',
                    'depth' => '1',
                    'name' => 'Science fiction'
                ],
                [
                    'id' => '4',
                    'parent_id' => '2',
                    'lft' => '2',
                    'rgt' => '3',
                    'root' => '2',
                    'depth' => '2',
                    'name' => 'Science fiction of Earth'
                ]
            ],
            BookCategory::objects()->values()
        );

        $fantasy->fetchTreePosition();
        $fantasy->delete();

        $this->assertEquals(
            [
                [
                    'id' => '2',
                    'parent_id' => null,
                    'lft' => '1',
                    'rgt' => '4',
                    'root' => '2',
                    'depth' => '1',
                    'name' => 'Science fiction'
                ],
                [
                    'id' => '4',
                    'parent_id' => '2',
                    'lft' => '2',
                    'rgt' => '3',
                    'root' => '2',
                    'depth' => '2',
                    'name' => 'Science fiction of Earth'
                ]
            ],
            BookCategory::objects()->values()
        );

        $science->fetchTreePosition();
        $science->delete();

        $this->assertEquals(
            [],
            BookCategory::objects()->values()
        );
    }
}