<?php

/*
 * This file is part of the eluceo/iCal package.
 *
 * (c) 2024 Markus Poerschke <markus@poerschke.nrw>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Eluceo\iCal\Domain\Collection;

use ArrayIterator;
use Eluceo\iCal\Domain\Entity\Todo;
use Iterator;

final class TodosArray extends Todos
{
    /**
     * @var array<int, Todo>
     */
    private array $todos = [];

    /**
     * @param array<array-key, Todo> $todos
     */
    public function __construct(array $todos)
    {
        array_walk($todos, [$this, 'addTodo']);
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->todos);
    }

    public function addTodo(Todo $todo): void
    {
        $this->todos[] = $todo;
    }
}
