<?php

/*
 * This file is part of the eluceo/iCal package.
 *
 * (c) 2024 Markus Poerschke <markus@poerschke.nrw>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Eluceo\iCal\Presentation\Factory;

use Eluceo\iCal\Domain\Collection\Todos;
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\Enum\TodoStatus;
use Eluceo\iCal\Domain\ValueObject\Alarm;
use Eluceo\iCal\Domain\ValueObject\Attachment;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Presentation\Component;
use Eluceo\iCal\Presentation\Component\Property;
use Eluceo\iCal\Presentation\Component\Property\Parameter;
use Eluceo\iCal\Presentation\Component\Property\Value\AppleLocationGeoValue;
use Eluceo\iCal\Presentation\Component\Property\Value\BinaryValue;
use Eluceo\iCal\Presentation\Component\Property\Value\DateTimeValue;
use Eluceo\iCal\Presentation\Component\Property\Value\GeoValue;
use Eluceo\iCal\Presentation\Component\Property\Value\IntegerValue;
use Eluceo\iCal\Presentation\Component\Property\Value\ListValue;
use Eluceo\iCal\Presentation\Component\Property\Value\TextValue;
use Eluceo\iCal\Presentation\Component\Property\Value\UriValue;
use Generator;
use UnexpectedValueException;

/**
 * @SuppressWarnings("CouplingBetweenObjects")
 */
class TodoFactory
{
    private AlarmFactory $alarmFactory;

    private AttendeeFactory $attendeeFactory;

    public function __construct(AlarmFactory $alarmFactory = null, AttendeeFactory $attendeeFactory = null)
    {
        $this->alarmFactory = $alarmFactory ?? new AlarmFactory();
        $this->attendeeFactory = $attendeeFactory ?? new AttendeeFactory();
    }

    /**
     * @return Generator<Component>
     */
    final public function createComponents(Todos $todos): Generator
    {
        foreach ($todos as $todo) {
            yield $this->createComponent($todo);
        }
    }

    public function createComponent(Todo $todo): Component
    {
        return new Component(
            'VTODO',
            iterator_to_array($this->getProperties($todo), false),
            iterator_to_array($this->getComponents($todo), false)
        );
    }

    /**
     * @return Generator<Property>
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getProperties(Todo $todo): Generator
    {
        yield new Property('UID', new TextValue((string) $todo->getUniqueIdentifier()));
        yield new Property('DTSTAMP', new DateTimeValue($todo->getTouchedAt()));

        if ($todo->hasLastModified()) {
            yield new Property('LAST-MODIFIED', new DateTimeValue($todo->getLastModified()));
        }

        if ($todo->hasSummary()) {
            yield new Property('SUMMARY', new TextValue($todo->getSummary()));
        }

        if ($todo->hasDescription()) {
            yield new Property('DESCRIPTION', new TextValue($todo->getDescription()));
        }

        if ($todo->hasUrl()) {
            yield new Property('URL', new TextValue($todo->getUrl()->getUri()));
        }

        if ($todo->hasDue()) {
            yield new Property('DUE', new DateTimeValue($todo->getDue()));
        }

        if ($todo->hasCompleted()) {
            yield new Property('COMPLETED', new DateTimeValue($todo->getCompleted()));
        }

        if ($todo->hasLocation()) {
            yield from $this->getLocationProperties($todo);
        }

        if ($todo->hasOrganizer()) {
            yield $this->getOrganizerProperty($todo->getOrganizer());
        }

        if ($todo->hasAttendee()) {
            foreach ($todo->getAttendees() as $attendee) {
                yield $this->attendeeFactory->createProperty($attendee);
            }
        }

        if ($todo->hasCategories()) {
            yield $this->getCategoryProperties($todo);
        }

        if ($todo->hasStatus()) {
            yield new Property('STATUS', $this->getTodoStatusTextValue($todo->getStatus()));
        }

        foreach ($todo->getAttachments() as $attachment) {
            yield from $this->getAttachmentProperties($attachment);
        }
    }

    /**
     * @return Generator<Component>
     */
    protected function getComponents(Todo $todo): Generator
    {
        yield from array_map(
            fn (Alarm $alarm) => $this->alarmFactory->createComponent($alarm),
            $todo->getAlarms()
        );
    }

    /**
     * @return Generator<Property>
     */
    private function getLocationProperties(Todo $todo): Generator
    {
        yield new Property('LOCATION', new TextValue((string) $todo->getLocation()));

        if ($todo->getLocation()->hasGeographicalPosition()) {
            yield new Property('GEO', new GeoValue($todo->getLocation()->getGeographicPosition()));
            yield new Property(
                'X-APPLE-STRUCTURED-LOCATION',
                new AppleLocationGeoValue($todo->getLocation()->getGeographicPosition()),
                [
                    new Parameter('VALUE', new TextValue('URI')),
                    new Parameter('X-ADDRESS', new TextValue((string) $todo->getLocation())),
                    new Parameter('X-APPLE-RADIUS', new IntegerValue(49)),
                    new Parameter('X-TITLE', new TextValue($todo->getLocation()->getTitle())),
                ]
            );
        }
    }

    /**
     * @return Generator<Property>
     */
    private function getAttachmentProperties(Attachment $attachment): Generator
    {
        $parameters = [];

        if ($attachment->hasMimeType()) {
            $parameters[] = new Parameter('FMTTYPE', new TextValue($attachment->getMimeType()));
        }

        if ($attachment->hasUri()) {
            yield new Property(
                'ATTACH',
                new UriValue($attachment->getUri()),
                $parameters
            );
        }

        if ($attachment->hasBinaryContent()) {
            $parameters[] = new Parameter('ENCODING', new TextValue('BASE64'));
            $parameters[] = new Parameter('VALUE', new TextValue('BINARY'));

            yield new Property(
                'ATTACH',
                new BinaryValue($attachment->getBinaryContent()),
                $parameters
            );
        }
    }

    private function getOrganizerProperty(Organizer $organizer): Property
    {
        $parameters = [];

        if ($organizer->hasDisplayName()) {
            $parameters[] = new Parameter('CN', new TextValue($organizer->getDisplayName()));
        }

        if ($organizer->hasDirectoryEntry()) {
            $parameters[] = new Parameter('DIR', new UriValue($organizer->getDirectoryEntry()));
        }

        if ($organizer->isSentInBehalfOf()) {
            $parameters[] = new Parameter('SENT-BY', new UriValue($organizer->getSentBy()->toUri()));
        }

        return new Property('ORGANIZER', new UriValue($organizer->getEmailAddress()->toUri()), $parameters);
    }

    private function getCategoryProperties(Todo $todo): Property
    {
        $categories = [];
        foreach ($todo->getCategories() as $category) {
            $categories[] = new TextValue((string) $category);
        }

        return new Property('CATEGORIES', new ListValue($categories));
    }

    private function getTodoStatusTextValue(TodoStatus $status): TextValue
    {
        if ($status === TodoStatus::NEEDS_ACTION()) {
            return new TextValue('NEEDS-ACTION');
        }

        if ($status === TodoStatus::COMPLETED()) {
            return new TextValue('COMPLETED');
        }

        if ($status === TodoStatus::IN_PROCESS()) {
            return new TextValue('IN-PROCESS');
        }

        if ($status === TodoStatus::CANCELLED()) {
            return new TextValue('CANCELLED');
        }

        throw new UnexpectedValueException(sprintf('The enum %s resulted into an unknown status type value that is not yet implemented.', TodoStatus::class));
    }
}
