---
title: Calendar
---

The calendar is basically a collection of events and/or to-do tasks.
A calendar can be represented as a `.ical` file.

## Adding events

Events can be either added via the named constructor:

```php
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;

$events = [
    new Event(),
    new Event(),
];

$calendar = new Calendar($events);
```

or calling the `addEvent` method:

```php
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;

$calendar = new Calendar();
$calendar
    ->addEvent(new Event())
    ->addEvent(new Event());
```

or providing a generator, that creates events:

```php
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;

$eventGenerator = function(): Generator {
    yield new Event();
    yield new Event();
};

$calendar = new Calendar($eventGenerator());
```

## Adding to-do tasks

To-do tasks can be added in the same way as events, either via the named constructor:

```php
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Todo;

$todos = [
    new Todo(),
    new Todo(),
];

$calendar = new Calendar([], $todos);
```

or calling the `addTodo` method:

```php
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Todo;

$calendar = new Calendar();
$calendar
    ->addTodo(new Todo())
    ->addTodo(new Todo());
```

or providing a generator, that creates to-do tasks:

```php
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Todo;

$todoGenerator = function(): Generator {
    yield new Todo();
    yield new Todo();
};

$calendar = new Calendar([], $todoGenerator());
```

## Adding time zones

When working with local times, time zone definitions should be added:

```php
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\TimeZone;
use DateTimeZone as PhpDateTimeZone;

$calendar = new Calendar();
$calendar
    ->addTimeZone(TimeZone::createFromPhpDateTimeZone(new PhpDateTimeZone('Europe/Berlin')))
    ->addTimeZone(TimeZone::createFromPhpDateTimeZone(new PhpDateTimeZone('Europe/London')))
;
```
