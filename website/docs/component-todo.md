---
title: To-do
---

The to-do domain object `\Eluceo\iCal\Domain\Entity\Todo` represents a to-do task.
For example, it can be to finalize and finish a work report which is due on 4th of July at 2 pm.

## Create new instance

When creating a new instance with the default construct method `new Todo()`, the optional parameter `$uniqueIdentifier` can be set.
If it is not set, then a random, but unique identifier is created.

```php
use Eluceo\iCal\Domain\Entity\Todo;

$todo = new Todo();
```

To set the properties, a fluent interface can be used:

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Date;

$todo = (new Todo())
    ->setSummary('Finalize Report')
    ->setDescription('Lorem Ipsum...')
    ->setDue(new SingleDay(new Date()));
```

## Properties

The following sections explain the properties of the domain object:

-   [Unique Identifier](#unique-identifier)
-   [Touched at](#touched-at)
-   [Summary](#summary)
-   [Description](#description)
-   [Due](#due)
-   [Completed](#completed)
-   [Location](#location)
-   [Organizer](#organizer)
-   [Attachments](#attachments)
-   [Attendee](#attendee)
-   [Categories](#categories)
-   [Status](#status)

### Unique Identifier

See [RFC 5545 section 3.8.4.7](https://tools.ietf.org/html/rfc5545#section-3.8.4.7).

A unique identifier must be a globally unique value.
When the value is generated, you must guarantee that it is unique.
Mostly this can be accomplished by adding the domain name to the identifier.

Given, the to-do task id is stored in `$myTodoUid`, than the to-do task can be created using that id with the following code:

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;

$myTodoUid = 'example.com/todo/1234';
$uniqueIdentifier = new UniqueIdentifier($myTodoUid);
$todo = new Todo($uniqueIdentifier);
```

### Touched at

The `$touchedAt` property is a `Timestamp` that indicates when the to-do tasked was changed.
If the to-do task was just created, the value is equal to the creation time.
Therefore, the default value will be the current time.
The value can be changed using the `touch` method.

```php
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Eluceo\iCal\Domain\Entity\Todo;

$todo = new Todo();
$todo->touch(new Timestamp());
```

A timestamp object can also be created from an object that implements `\DateTimeInterface` like this:

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Timestamp;

$todo = new Todo();
$dateTime = DateTimeImmutable::createFromFormat('Y-m-d', '2019-12-24');
$timestamp = new Timestamp($dateTime);
$todo->touch($timestamp);
```

### Summary

The summary of a to-do task is a short, single line text, that describes the to-do task.

```php
use Eluceo\iCal\Domain\Entity\Todo;

$todo = new Todo();
$todo->setSummary('Finalize Report');
```

### Description

In addition to the summary, the description gives more information about the to-do task.

```php
use Eluceo\iCal\Domain\Entity\Todo;

$todo = new Todo();
$todo->setDescription('Lorem Ipsum Dolor...');
```

### URL

The URL can be used to link to an arbitrary resource.

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Uri;

$todo = new Todo();
$uri = new Uri("https://example.org/calendartodo");
$todo->setUrl($uri);
```

### Due

The due property of a to-do task is a `Timestamp` and defines, when the to-do task is due.

The to-do task will be due on the specified date.

The following example shows how to set the due date for a to-do task that is due on 4th of July 2024 at 2 pm:

```php
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\Entity\Todo;

$todo = new Todo();
$due = new Timestamp(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2024-07-04 14:00:00', new \DateTimeZone('UTC')));
$todo->setDue($due);
```

### Completed

The completed property of a to-do task is a `Timestamp` and defines, when the to-do task was completed.

The following example shows how to set the completed property for a to-do task that was completed on 24th of December 2023 at 12 pm:

```php
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Eluceo\iCal\Domain\Entity\Todo;

$todo = new Todo();
$completed= new Timestamp(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2023-12-24 12:00:00', new \DateTimeZone('UTC')));
$todo->setCompleted($completed);
```

### Location

The location defines where a to-do task takes place.
The value can be a generic name like the name of a meeting room or an address.
As an optional property, the exact [geographic position](https://en.wikipedia.org/wiki/Geographic_coordinate_system#Latitude_and_longitude) can be added.

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\GeographicPosition;

$location = new Location('Neuschwansteinstraße 20, 87645 Schwangau');

// optionally you can create a location with a title for X-APPLE-STRUCTURED-LOCATION attribute
$location = new Location('Neuschwansteinstraße 20, 87645 Schwangau', 'Schloss Neuschwanstein');

// optionally a location with a geographical position can be created
$location = $location->withGeographicPosition(new GeographicPosition(47.557579, 10.749704));

$todo = new Todo();
$todo->setLocation($location);
```

### Organizer

The Organizer defines the person who organises the to-do task.
The property consists of at least an email address.
Optional a display name, or a directory entry (as used in LDAP for example) can be added.
In case the to-do task was sent in behalf of another person, then the `sendBy` attribute will contain the email address.

```php
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\Uri;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\Entity\Todo;

$organizer = new Organizer(
    new EmailAddress('test@example.org'),
    'John Doe',
    new Uri('ldap://example.com:6666/o=ABC%20Industries,c=US???(cn=Jim%20Dolittle)'),
    new EmailAddress('sender@example.com')
);

$todo = new Todo();
$todo->setOrganizer($organizer);
```

### Attachments

A document can be associated with a to-do task.
It can be either be added as a URI or directly embedded as binary content.
It is strongly recommended to use the URI attachment, since binary content is not supported by all calendar applications.

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Attachment;
use Eluceo\iCal\Domain\ValueObject\BinaryContent;
use Eluceo\iCal\Domain\ValueObject\Uri;

$urlAttachment = new Attachment(
    new Uri('https://example.com/test.txt'),
    'text/plain'
);

$binaryContentAttachment = new Attachment(
    new BinaryContent(file_get_contents('test.txt')),
    'text/plain'
);

$todo = new Todo();
$todo->addAttachment($urlAttachment);
$todo->addAttachment($binaryContentAttachment);
```

### Attendee

This property defines one or more attendee/s related to the to-do task.
Calendar user type, group or list membership, participation role, participation status, RSVP expectation, delegatee, delegator, sent by, common name, or directory entry reference property parameters can be specified on this property.
Therefore are listed all the possible methods that you can call on the attendee

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\Enum\ParticipationStatus;
use Eluceo\iCal\Domain\Enum\RoleType;
USE Eluceo\iCal\Domain\Enum\CalendarUserType;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\BinaryContent;
use Eluceo\iCal\Domain\ValueObject\Uri;

$attendee = new Attendee(new EmailAddress('jdoe@example.com'));
$attendee->setCalendarUserType(CalendarUserType::INDIVIDUAL())
    ->addMember(new Member(new EmailAddress('test@example.com')))
    ->setRole(RoleType::CHAIR())
    ->setParticipationStatus(
        ParticipationStatus::NEEDS_ACTION()
    )->setResponseNeededFromAttendee(true)
    ->addDelegatedTo(
        new EmailAddress('jdoe@example.com')
    )->addDelegatedTo(
        new EmailAddress('jqpublic@example.com')
    )->addDelegatedFrom(
        new EmailAddress('jsmith@example.com')
    )->addSentBy(
        new EmailAddress('sray@example.com')
    )
    ->setDisplayName('Test Example')
    ->setDirectoryEntryReference(
        new Uri('ldap://example.com:6666/o=ABC%20Industries,c=US???(cn=Jim%20Dolittle)')
    )->setLanguage('en-US');

$todo = (new Todo())
    ->addAttendee($attendee);

$todo = new Todo();
$todo->addAttachment($urlAttachment);
$todo->addAttachment($binaryContentAttachment);
```

### Categories

This property is used to specify categories or subtypes of the calendar component.
The categories are useful in searching for a calendar component of a particular type and category.

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Category;

$todo = new Todo();
$todo
    ->addCategory(new Category('APPOINTMENT'))
    ->addCategory(new Category('EDUCATION'));
```

### Status

This property defines the status of the to-do task, e.g. if it is in progress or perhaps cancelled. The possible values
are `completed`, `in process`, `needs action`, and `cancelled`.

```php
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\Enum\TodoStatus;

$todo = new Todo();
$todo->setStatus(TodoStatus::CANCELLED());
```
