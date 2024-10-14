<?php

/*
 * This file is part of the eluceo/iCal package.
 *
 * (c) 2024 Markus Poerschke <markus@poerschke.nrw>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Unit\Presentation\Factory;

use DateTimeImmutable;
use DateTimeZone;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\Enum\CalendarUserType;
use Eluceo\iCal\Domain\Enum\ParticipationStatus;
use Eluceo\iCal\Domain\Enum\RoleType;
use Eluceo\iCal\Domain\Enum\TodoStatus;
use Eluceo\iCal\Domain\ValueObject\Attachment;
use Eluceo\iCal\Domain\ValueObject\BinaryContent;
use Eluceo\iCal\Domain\ValueObject\Category;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\GeographicPosition;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\Member;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\Timestamp;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Domain\ValueObject\Uri;
use Eluceo\iCal\Presentation\ContentLine;
use Eluceo\iCal\Presentation\Factory\TodoFactory;
use PHPUnit\Framework\TestCase;

class TodoFactoryTest extends TestCase
{
    public function testMinimalTodo()
    {
        $currentTime = new Timestamp(
            DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                '2019-11-10 11:22:33',
                new DateTimeZone('UTC')
            )
        );

        $lastModified = new Timestamp(
            DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                '2019-10-09 10:11:22',
                new DateTimeZone('UTC')
            )
        );

        $todo = (new Todo(new UniqueIdentifier('todo1')))
            ->touch($currentTime)
            ->setLastModified($lastModified)
        ;

        $expected = implode(ContentLine::LINE_SEPARATOR, [
            'BEGIN:VTODO',
            'UID:todo1',
            'DTSTAMP:20191110T112233Z',
            'LAST-MODIFIED:20191009T101122Z',
            'END:VTODO',
            '',
        ]);

        self::assertSame($expected, (string) (new TodoFactory())->createComponent($todo));
    }

    public function testTodoWithSummaryAndDescription()
    {
        $todo = (new Todo())
            ->setSummary('Lorem Summary')
            ->setDescription('Lorem Description');

        self::assertTodoRendersCorrect($todo, [
            'SUMMARY:Lorem Summary',
            'DESCRIPTION:Lorem Description',
        ]);
    }

    public function testTodoWithLocation()
    {
        $geographicalPosition = new GeographicPosition(51.333333333333, 7.05);
        $location = (new Location('Location Name', 'Somewhere'))->withGeographicPosition($geographicalPosition);
        $todo = (new Todo())->setLocation($location);

        self::assertTodoRendersCorrect(
            $todo,
            [
                'LOCATION:Location Name',
                'GEO:51.333333;7.050000',
                'X-APPLE-STRUCTURED-LOCATION;VALUE=URI;X-ADDRESS=Location Name;X-APPLE-RADIU',
                ' S=49;X-TITLE=Somewhere:geo:51.333333,7.050000',
            ]
        );
    }

    public function testTodoDue()
    {
        $due = new Timestamp(DateTimeImmutable::createFromFormat('Y-m-d H:i', '2030-12-24 12:15', new DateTimeZone('UTC')));
        $todo = (new Todo())->setDue($due);

        self::assertTodoRendersCorrect($todo, [
            'DUE:20301224T121500Z',
        ]);
    }

    public function testTodoCompleted()
    {
        $completed = new Timestamp(DateTimeImmutable::createFromFormat('Y-m-d H:i', '2030-12-24 12:15', new DateTimeZone('UTC')));
        $todo = (new Todo())->setCompleted($completed);

        self::assertTodoRendersCorrect($todo, [
            'COMPLETED:20301224T121500Z',
        ]);
    }

    public function testUrlAttachments()
    {
        $todo = (new Todo())
            ->addAttachment(
                new Attachment(
                    new Uri('http://example.com/document.txt'),
                    'text/plain')
            );

        self::assertTodoRendersCorrect($todo, [
            'ATTACH;FMTTYPE=text/plain:http://example.com/document.txt',
        ]);
    }

    public function testFileAttachments()
    {
        $todo = (new Todo())
            ->addAttachment(
                new Attachment(
                    new BinaryContent('Hello World!'),
                    'text/plain'
                )
            );

        self::assertTodoRendersCorrect($todo, [
            'ATTACH;FMTTYPE=text/plain;ENCODING=BASE64;VALUE=BINARY:SGVsbG8gV29ybGQh',
        ]);
    }

    public function testOrganizer()
    {
        $todo = (new Todo())
            ->setOrganizer(new Organizer(
                new EmailAddress('test@example.com'),
                'Test Display Name',
                new Uri('example://directory-entry'),
                new EmailAddress('sendby@example.com')
            ));

        self::assertTodoRendersCorrect($todo, [
            'ORGANIZER;CN=Test Display Name;DIR=example://directory-entry;SENT-BY=mailto',
            ' :sendby@example.com:mailto:test@example.com',
        ]);
    }

    public function testOneAttendee()
    {
        $todo = (new Todo())
            ->addAttendee(new Attendee(
                new EmailAddress('test@example.com')
            ));

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE:mailto:test@example.com',
        ]);
    }

    public function testMultipleAttendees()
    {
        $todo = (new Todo())
            ->addAttendee(new Attendee(
                new EmailAddress('test@example.com')
            ))
            ->addAttendee(new Attendee(
                new EmailAddress('test2@example.net')
            ));

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE:mailto:test@example.com',
            'ATTENDEE:mailto:test2@example.net',
        ]);
    }

    /*  public function testAttendeeWithCN()
     {
         $todo = (new Todo())
             ->addAttendee(new Attendee(
                 new EmailAddress('test@example.com'),
                 null,
                 'Test Display Name',
             ));

         self::assertTodoRendersCorrect($todo, [
             'ATTENDEE;CN=Test Display Name:mailto:test@example.com',
         ]);
     } */

    public function testAttendeeWithIndividualCUtype()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setCalendarUserType(CalendarUserType::INDIVIDUAL());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CUTYPE=INDIVIDUAL:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithGroupCUtype()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setCalendarUserType(CalendarUserType::GROUP());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CUTYPE=GROUP:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithResourceCUtype()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setCalendarUserType(CalendarUserType::RESOURCE());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CUTYPE=RESOURCE:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithRoomCUtype()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setCalendarUserType(CalendarUserType::ROOM());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CUTYPE=ROOM:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithUnknownCUtype()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setCalendarUserType(CalendarUserType::UNKNOWN());

        $todo = (new Todo())
            ->addAttendee($attendee);
        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CUTYPE=UNKNOWN:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithOneMember()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setCalendarUserType(CalendarUserType::INDIVIDUAL());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CUTYPE=INDIVIDUAL:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithMultipleMembers()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setCalendarUserType(CalendarUserType::INDIVIDUAL())
            ->addMember(new Member(new EmailAddress('test@example.com')))
            ->addMember(new Member(new EmailAddress('test@example.net')));

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CUTYPE=INDIVIDUAL;MEMBER="mailto:test@example.com","mailto:test@ex',
            ' ample.net":mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithChairRole()
    {
        $attendee = new Attendee(new EmailAddress('test@example.com'));
        $attendee->setRole(RoleType::CHAIR());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;ROLE=CHAIR:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithReqParticipantRole()
    {
        $attendee = new Attendee(
            new EmailAddress('test@example.com'),
        );
        $attendee->setRole(RoleType::REQ_PARTICIPANT());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;ROLE=REQ-PARTICIPANT:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithParticipationStatusNeedsAction()
    {
        $attendee = new Attendee(
            new EmailAddress('test@example.com'),
        );

        $attendee->setParticipationStatus(ParticipationStatus::NEEDS_ACTION());

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;PARTSTAT=NEEDS-ACTION:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithRSVP()
    {
        $attendee = new Attendee(
            new EmailAddress('test@example.com'),
        );

        $attendee->setResponseNeededFromAttendee(true);

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;RSVP=TRUE:mailto:test@example.com',
        ]);
    }

    public function testAttendeeWithDelegatedTo()
    {
        $attendee = new Attendee(
            new EmailAddress('jsmith@example.com'),
        );

        $attendee->addDelegatedTo(
            new EmailAddress('jdoe@example.com')
        )->addDelegatedTo(
            new EmailAddress('jqpublic@example.com')
        );

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;DELEGATED-TO="mailto:jdoe@example.com","mailto:jqpublic@example.co',
            ' m":mailto:jsmith@example.com',
        ]);
    }

    public function testAttendeeWithDelegatedFrom()
    {
        $attendee = new Attendee(
            new EmailAddress('jdoe@example.com'),
        );

        $attendee->addDelegatedFrom(
            new EmailAddress('jsmith@example.com')
        );

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;DELEGATED-FROM="mailto:jsmith@example.com":mailto:jdoe@example.com',
        ]);
    }

    public function testAttendeeWithSentBy()
    {
        $attendee = new Attendee(
            new EmailAddress('jdoe@example.com'),
        );

        $attendee->addSentBy(
            new EmailAddress('sray@example.com')
        );

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;SENT-BY="mailto:sray@example.com":mailto:jdoe@example.com',
        ]);
    }

    public function testAttendeeWithCommonName()
    {
        $attendee = new Attendee(
            new EmailAddress('jdoe@example.com'),
        );

        $attendee->setDisplayName('Test Example');

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;CN=Test Example:mailto:jdoe@example.com',
        ]);
    }

    public function testAttendeeWithDirectoryEntryRef()
    {
        $attendee = new Attendee(
            new EmailAddress('jdoe@example.com'),
        );

        $attendee->setDirectoryEntryReference(new Uri('ldap://example.com:6666/o=ABC%20Industries,c=US???(cn=Jim%20Dolittle)'));

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;DIR="ldap://example.com:6666/o=ABC%20Industries,c=US???(cn=Jim%20D',
            ' olittle)":mailto:jdoe@example.com',
        ]);
    }

    public function testAttendeeWithLanguage()
    {
        $attendee = new Attendee(
            new EmailAddress('jdoe@example.com'),
        );

        $attendee->setLanguage('en-US');

        $todo = (new Todo())
            ->addAttendee($attendee);

        self::assertTodoRendersCorrect($todo, [
            'ATTENDEE;LANGUAGE=en-US:mailto:jdoe@example.com',
        ]);
    }

    public function testTodoUrl()
    {
        $todo = (new Todo())
            ->setUrl(new Uri('https://example.org/calendarevent'));

        self::assertTodoRendersCorrect($todo, [
            'URL:https://example.org/calendarevent',
        ]);
    }

    public function testTodoWithOneCategory()
    {
        $category = new Category('category');
        $todo = (new Todo())->addCategory($category);

        self::assertTodoRendersCorrect(
            $todo,
            [
                'CATEGORIES:category',
            ]
        );
    }

    public function testTodoWithMultipleCategories()
    {
        $todo = (new Todo())
            ->addCategory(new Category('category 1'))
            ->addCategory(new Category('category 2'));

        self::assertTodoRendersCorrect(
            $todo,
            [
                'CATEGORIES:category 1,category 2',
            ]
        );
    }

    public function testTodoWithCancelledStatus(): void
    {
        $todo = (new Todo())->setStatus(TodoStatus::CANCELLED());

        self::assertTodoRendersCorrect($todo, [
            'STATUS:CANCELLED',
        ]);
    }

    public function testTodoWithCompletedStatus(): void
    {
        $todo = (new Todo())->setStatus(TodoStatus::COMPLETED());

        self::assertTodoRendersCorrect($todo, [
            'STATUS:COMPLETED',
        ]);
    }

    public function testTodoWithInProgressStatus(): void
    {
        $todo = (new Todo())->setStatus(TodoStatus::IN_PROCESS());

        self::assertTodoRendersCorrect($todo, [
            'STATUS:IN-PROCESS',
        ]);
    }

    public function testTodoWithNeedsActionStatus(): void
    {
        $todo = (new Todo())->setStatus(TodoStatus::NEEDS_ACTION());

        self::assertTodoRendersCorrect($todo, [
            'STATUS:NEEDS-ACTION',
        ]);
    }

    private static function assertTodoRendersCorrect(Todo $todo, array $expected)
    {
        $resultAsString = (string) (new TodoFactory())->createComponent($todo);

        $resultAsArray = explode(ContentLine::LINE_SEPARATOR, $resultAsString);

        self::assertGreaterThan(5, count($resultAsArray), 'No additional content lines were produced.');

        $resultAsArray = array_slice($resultAsArray, 3, -2);
        self::assertSame($expected, $resultAsArray);
    }
}
