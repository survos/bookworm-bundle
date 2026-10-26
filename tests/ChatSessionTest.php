<?php

declare(strict_types=1);

namespace Survos\BookwormBundle\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Survos\BookwormBundle\Chat\ChatSession;
use Survos\BookwormBundle\Corpus\CorpusRegistry;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\Execution\ExecutionInterface;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ChatSessionTest extends TestCase
{
    public function testHistorySurvivesImmutableMessagesAndReset(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requests = new RequestStack();
        $requests->push($request);
        $execution = $this->createStub(ExecutionInterface::class);
        $execution->method('asText')->willReturn('Answer');
        $bags = [];
        $agent = $this->createMock(AgentInterface::class);
        $agent->expects(self::exactly(2))->method('call')->willReturnCallback(function (MessageBag $messages) use (&$bags, $execution): ExecutionInterface {
            $bags[] = $messages;

            return $execution;
        });
        $agents = $this->createStub(ContainerInterface::class);
        $agents->method('get')->willReturn($agent);
        $chat = new ChatSession($requests, new CorpusRegistry(['docs' => ['directory' => '/tmp/docs', 'indexer' => 'docs']]), $agents);
        $chat->submit('docs', 'First question');
        $chat->submit('docs', 'Second question');
        self::assertCount(1, $bags[0]->getMessages());
        self::assertCount(3, $bags[1]->getMessages());
        self::assertSame('First question', $bags[1]->getMessages()[0]->getContent()[0]->getText());
        self::assertSame('Answer', $bags[1]->getMessages()[1]->getContent()[0]->getText());
        self::assertSame('Second question', $bags[1]->getMessages()[2]->getContent()[0]->getText());
        self::assertCount(4, $chat->messages('docs'));
        $chat->reset('docs');
        self::assertSame([], $chat->messages('docs'));
    }
}
