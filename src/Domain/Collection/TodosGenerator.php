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

use BadMethodCallException;
use Eluceo\iCal\Domain\Entity\Todo;
use Iterator;

final class TodosGenerator extends Todos
{
    /**
     * @var Iterator<Todo>
     */
    private Iterator $generator;

    /**
     * @param Iterator<Todo> $generator
     */
    public function __construct(Iterator $generator)
    {
        $this->generator = $generator;
    }

    public function getIterator(): Iterator
    {
        return $this->generator;
    }

    public function addTodo(Todo $todo): void
    {
        throw new BadMethodCallException('Todos cannot be added to an TodosGenerator.');
    }
}
