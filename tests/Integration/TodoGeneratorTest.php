<?php

/*
 * This file is part of the eluceo/iCal package.
 *
 * (c) 2024 Markus Poerschke <markus@poerschke.nrw>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Eluceo\iCal\Test\Integration;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Generator;
use PHPUnit\Framework\TestCase;

class TodosGeneratorTest extends TestCase
{
    public function testTodosGeneratorCreatesIcsContent(): void
    {
        $todoGenerator = function (): Generator {
            $day = new DateTimeImmutable('2020-01-01 18:00:00', new DateTimeZone('UTC'));
            $timestamp = new Timestamp($day);
            $dayInterval = new DateInterval('P1D');
            for ($i = 0; $i < 3; ++$i) {
                yield (new Todo(new UniqueIdentifier('todo-' . $i)))
                    ->touch($timestamp)
                    ->setSummary('To-do ' . $i)
                    ->setDue(new Timestamp($day));
                $day = $day->add($dayInterval);
            }
        };

        $calendar = new Calendar([], $todoGenerator());
        $componentFactory = new CalendarFactory();
        $calendarComponent = $componentFactory->createCalendar($calendar);

        $expected = [
            'BEGIN:VCALENDAR',
            'PRODID:-//eluceo/ical//2.0/EN',
            'VERSION:2.0',
            'CALSCALE:GREGORIAN',
            'BEGIN:VTODO',
            'UID:todo-0',
            'DTSTAMP:20200101T180000Z',
            'SUMMARY:To-do 0',
            'DUE:20200101T180000Z',
            'END:VTODO',
            'BEGIN:VTODO',
            'UID:todo-1',
            'DTSTAMP:20200101T180000Z',
            'SUMMARY:To-do 1',
            'DUE:20200102T180000Z',
            'END:VTODO',
            'BEGIN:VTODO',
            'UID:todo-2',
            'DTSTAMP:20200101T180000Z',
            'SUMMARY:To-do 2',
            'DUE:20200103T180000Z',
            'END:VTODO',
            'END:VCALENDAR',
        ];
        $contentLines = array_map('trim', iterator_to_array($calendarComponent, false));

        self::assertSame($expected, $contentLines);
    }
}
