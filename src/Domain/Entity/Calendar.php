<?php

/*
 * This file is part of the eluceo/iCal package.
 *
 * (c) 2024 Markus Poerschke <markus@poerschke.nrw>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Eluceo\iCal\Domain\Entity;

use DateInterval;
use Eluceo\iCal\Domain\Collection\Events;
use Eluceo\iCal\Domain\Collection\EventsArray;
use Eluceo\iCal\Domain\Collection\EventsGenerator;
use Eluceo\iCal\Domain\Collection\Todos;
use Eluceo\iCal\Domain\Collection\TodosArray;
use Eluceo\iCal\Domain\Collection\TodosGenerator;
use InvalidArgumentException;
use Iterator;

class Calendar
{
    private string $productIdentifier = '-//eluceo/ical//2.0/EN';

    private ?DateInterval $publishedTTL = null;

    private Events $events;

    private Todos $todos;

    /**
     * @var array<TimeZone>
     */
    private array $timeZones = [];

    /**
     * @param array<array-key, Event>|Iterator<Event>|Events $events
     * @param array<array-key, Todo>|Iterator<Todo>|Todos    $todos
     */
    public function __construct($events = [], $todos = [])
    {
        $this->events = $this->ensureEventsObject($events);
        $this->todos = $this->ensureTodosObject($todos);
    }

    /**
     * @param array<array-key, Event>|Iterator<Event>|Events $events
     */
    private function ensureEventsObject($events = []): Events
    {
        if ($events instanceof Events) {
            return $events;
        }

        if (is_array($events)) {
            return new EventsArray($events);
        }

        if ($events instanceof Iterator) {
            return new EventsGenerator($events);
        }

        throw new InvalidArgumentException('$events must be an array, an object implementing Iterator or an instance of Events.');
    }

    /**
     * @param array<array-key, Todo>|Iterator<Todo>|Todos $todos
     */
    private function ensureTodosObject($todos = []): Todos
    {
        if ($todos instanceof Todos) {
            return $todos;
        }

        if (is_array($todos)) {
            return new TodosArray($todos);
        }

        if ($todos instanceof Iterator) {
            return new TodosGenerator($todos);
        }

        throw new InvalidArgumentException('$todos must be an array, an object implementing Iterator or an instance of Todos.');
    }

    public function getPublishedTTL(): ?DateInterval
    {
        return $this->publishedTTL;
    }

    public function setPublishedTTL(?DateInterval $ttl): self
    {
        $this->publishedTTL = $ttl;

        return $this;
    }

    public function getProductIdentifier(): string
    {
        return $this->productIdentifier;
    }

    public function setProductIdentifier(string $productIdentifier): self
    {
        $this->productIdentifier = $productIdentifier;

        return $this;
    }

    public function getEvents(): Events
    {
        return $this->events;
    }

    public function addEvent(Event $event): self
    {
        $this->events->addEvent($event);

        return $this;
    }

    public function getTodos(): Todos
    {
        return $this->todos;
    }

    public function addTodo(Todo $todo): self
    {
        $this->todos->addTodo($todo);

        return $this;
    }

    /**
     * @return array<TimeZone>
     */
    public function getTimeZones(): array
    {
        return $this->timeZones;
    }

    public function addTimeZone(TimeZone $timeZone): self
    {
        $this->timeZones[] = $timeZone;

        return $this;
    }
}
