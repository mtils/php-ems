<?php

namespace Ems\Mail\Swift;

use ArrayObject;
use Mockery as m;
use Ems\TestCase;
use Ems\Contracts\Mail\Transport;
use Ems\Contracts\Mail\MailConfigProvider;
use Ems\Contracts\Mail\MessageComposer;
use Ems\Testing\LoggingCallable;
use Ems\Testing\Cheat;
use Swift_Message;


class MessageTest extends TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf('Ems\Contracts\Mail\Message', $this->newMessage());
    }

    public function test_clearRecipientHeaders_removes_all_to_addresses()
    {

        $message = $this->newMessage();

        $message->to('foo@bar.de');

        $swift = $message->_swiftMessage();

        $this->assertTrue(address_exists($swift, 'to'));

        $message->clearRecipientHeaders();

        $this->assertFalse(address_exists($swift, 'to'));

        $message->to('foo@bar.de');

        $message->to('bar@foo.de');

        $this->assertTrue(address_exists($swift, 'to'));

        $message->clearRecipientHeaders();

        $this->assertFalse(address_exists($swift, 'to'));

    }

    public function test_clearRecipientHeaders_removes_all_cc_addresses()
    {

        $message = $this->newMessage();

        $message->cc('foo@bar.de');

        $swift = $message->_swiftMessage();

        $this->assertTrue(address_exists($swift, 'cc'));

        $message->clearRecipientHeaders();

        $this->assertFalse(address_exists($swift, 'cc'));

        $message->cc('foo@bar.de');

        $message->cc('bar@foo.de');

        $this->assertTrue(address_exists($swift, 'cc'));

        $message->clearRecipientHeaders();

        $this->assertFalse(address_exists($swift, 'cc'));
    }

    public function test_clearRecipientHeaders_removes_all_bcc_addresses()
    {

        $message = $this->newMessage();

        $message->bcc('foo@bar.de');

        $swift = $message->_swiftMessage();

        $this->assertTrue(address_exists($swift, 'bcc'));

        $message->clearRecipientHeaders();

        $this->assertFalse(address_exists($swift, 'bcc'));

        $message->bcc('foo@bar.de');

        $message->bcc('bar@foo.de');

        $this->assertTrue(address_exists($swift, 'bcc'));

        $message->clearRecipientHeaders();

        $this->assertFalse(address_exists($swift, 'bcc'));
    }

    public function test_clearRecipientHeaders_removes_all_addresses()
    {

        $message = $this->newMessage();

        $message->to('somebody@bar.de');
        $message->to('other@bar.de');
        $message->cc('nobody@bar.de');
        $message->cc('someone@bar.de');
        $message->bcc('foo@bar.de');
        $message->bcc('bar@foo.de');

        $swift = $message->_swiftMessage();

        $this->assertTrue(address_exists($swift, 'to'));
        $this->assertTrue(address_exists($swift, 'cc'));
        $this->assertTrue(address_exists($swift, 'bcc'));

        $message->clearRecipientHeaders();

        $this->assertFalse(address_exists($swift, 'to'));
        $this->assertFalse(address_exists($swift, 'cc'));
        $this->assertFalse(address_exists($swift, 'bcc'));
    }

    protected function newMessage(Swift_Message $message = null)
    {
        $message = $message ?: new Swift_Message;
        return new Message($message);
    }

    protected function mockTransport()
    {
        return $this->mock('Ems\Contracts\Mail\Transport');
    }

    protected function mockConfigProvider()
    {
        return $this->mock('Ems\Contracts\Mail\MailConfigProvider');
    }

    protected function mockComposer()
    {
        return $this->mock('Ems\Contracts\Mail\MessageComposer');
    }

    protected function mockMessage()
    {
        return $this->mock('Ems\Contracts\Mail\Message');
    }

    protected function mockConfig()
    {
        return $this->mock('Ems\Contracts\Mail\MailConfig');
    }

}

function address_exists(Swift_Message $message, $name) {

    foreach ($message->getHeaders()->listAll() as $header) {
        if (strtolower($header) == strtolower($name)) {
            return true;
        }
    }

    return false;

}
