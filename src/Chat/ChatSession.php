<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Chat;

use Psr\Container\ContainerInterface;
use Survos\BookwormBundle\Corpus\CorpusRegistry;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\{Message,MessageBag};
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ChatSession
{
    public function __construct(private RequestStack $requests, private CorpusRegistry $corpora,
        private ContainerInterface $agents) {}

    /** @return list<array{role: string, content: string}> */
    public function messages(string $corpus): array
    {
        return $this->requests->getSession()->get($this->key($corpus), []);
    }

    public function submit(string $corpus, string $question): void
    {
        $definition = $this->corpora->get($corpus); $agent = $this->agents->get($definition->agent);
        if (!$agent instanceof AgentInterface) { throw new \LogicException('Configured Bookworm agent is invalid.'); }
        $history = $this->messages($corpus); $bag = new MessageBag();
        foreach ($history as $item) {
            $bag->add('user' === $item['role'] ? Message::ofUser($item['content']) : Message::ofAssistant($item['content']));
        }
        $bag->add(Message::ofUser($question)); $answer = $agent->call($bag)->asText();
        $history[] = ['role' => 'user', 'content' => $question];
        $history[] = ['role' => 'assistant', 'content' => $answer];
        $this->requests->getSession()->set($this->key($corpus), $history);
    }

    public function reset(string $corpus): void { $this->requests->getSession()->remove($this->key($corpus)); }
    private function key(string $corpus): string { return 'bookworm.chat.'.$corpus; }
}
